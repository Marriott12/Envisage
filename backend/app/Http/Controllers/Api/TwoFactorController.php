<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Enable 2FA for user
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled'
            ], 400);
        }

        // Generate secret key
        $secret = $this->google2fa->generateSecretKey();
        
        // Store secret temporarily (will be confirmed later)
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        // Generate QR code URL (frontend will generate the actual QR code image)
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
        $user->two_factor_backup_codes = encrypt(json_encode($backupCodes));
        $user->save();

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'backup_codes' => $backupCodes,
            'message' => 'Scan the QR code with your authenticator app and verify with a code'
        ]);
    }

    /**
     * Verify and confirm 2FA setup
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'message' => 'Two-factor authentication not initialized'
            ], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 400);
        }

        // Enable 2FA
        $user->two_factor_enabled = true;
        $user->save();

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully'
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 400);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled'
            ], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 400);
        }

        // Disable 2FA
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_backup_codes = null;
        $user->save();

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully'
        ]);
    }

    /**
     * Validate 2FA code during login
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'code' => 'required|string|size:6'
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        if (!$user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled'
            ], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            // Check backup codes
            if ($this->checkBackupCode($user, $request->code)) {
                return response()->json([
                    'valid' => true,
                    'message' => 'Backup code used successfully. Please generate new backup codes.'
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Code verified successfully'
        ]);
    }

    /**
     * Generate new backup codes
     */
    public function regenerateBackupCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled'
            ], 400);
        }

        $backupCodes = $this->generateBackupCodes();
        $user->two_factor_backup_codes = encrypt(json_encode($backupCodes));
        $user->save();

        return response()->json([
            'backup_codes' => $backupCodes,
            'message' => 'New backup codes generated successfully'
        ]);
    }

    /**
     * Generate backup codes
     */
    protected function generateBackupCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        }
        return $codes;
    }

    /**
     * Check if backup code is valid
     */
    protected function checkBackupCode($user, $code)
    {
        if (!$user->two_factor_backup_codes) {
            return false;
        }

        $backupCodes = json_decode(decrypt($user->two_factor_backup_codes), true);
        
        if (in_array(strtoupper($code), $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_diff($backupCodes, [strtoupper($code)]);
            $user->two_factor_backup_codes = encrypt(json_encode(array_values($backupCodes)));
            $user->save();
            return true;
        }

        return false;
    }
}
