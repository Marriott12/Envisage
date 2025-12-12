<?php

namespace App\Jobs;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\User;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAutomationRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $execution;

    /**
     * Create a new job instance.
     */
    public function __construct(AutomationExecution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $rule = $this->execution->rule;
            $user = $this->execution->user;

            // Check if conditions are met
            if (!$this->checkConditions($rule->conditions, $user, $this->execution->data)) {
                $this->execution->markAsFailed('Conditions not met');
                return;
            }

            // Execute actions
            foreach ($rule->actions as $action) {
                $this->executeAction($action, $user, $this->execution->data);
            }

            // Mark as executed
            $this->execution->markAsExecuted();
            $rule->incrementExecutions();

            Log::info("Automation rule executed successfully", [
                'rule_id' => $rule->id,
                'execution_id' => $this->execution->id,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            $this->execution->markAsFailed($e->getMessage());

            Log::error("Automation rule execution failed", [
                'rule_id' => $this->execution->rule_id,
                'execution_id' => $this->execution->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if automation conditions are met
     */
    protected function checkConditions($conditions, $user, $data)
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            // Get actual value from user or data
            if (strpos($field, 'user.') === 0) {
                $actualValue = data_get($user, substr($field, 5));
            } else {
                $actualValue = data_get($data, $field);
            }

            // Check condition
            if (!$this->evaluateCondition($actualValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition($actualValue, $operator, $expectedValue)
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actualValue == $expectedValue;
            case '!=':
                return $actualValue != $expectedValue;
            case '>':
                return $actualValue > $expectedValue;
            case '>=':
                return $actualValue >= $expectedValue;
            case '<':
                return $actualValue < $expectedValue;
            case '<=':
                return $actualValue <= $expectedValue;
            case 'contains':
                return strpos($actualValue, $expectedValue) !== false;
            case 'not_contains':
                return strpos($actualValue, $expectedValue) === false;
            default:
                return false;
        }
    }

    /**
     * Execute automation action
     */
    protected function executeAction($action, $user, $data)
    {
        $actionType = $action['type'] ?? null;

        switch ($actionType) {
            case 'send_email':
                $this->sendEmail($action, $user, $data);
                break;

            case 'send_sms':
                $this->sendSms($action, $user, $data);
                break;

            case 'add_tag':
                $this->addTag($action, $user);
                break;

            case 'update_field':
                $this->updateField($action, $user);
                break;

            case 'trigger_webhook':
                $this->triggerWebhook($action, $user, $data);
                break;

            default:
                Log::warning("Unknown automation action type: {$actionType}");
        }
    }

    /**
     * Send email action
     */
    protected function sendEmail($action, $user, $data)
    {
        $templateId = $action['template_id'] ?? null;

        if (!$templateId) {
            throw new \Exception('Email template ID not provided');
        }

        $template = EmailTemplate::findOrFail($templateId);

        $variables = array_merge([
            'user_name' => $user->name,
            'user_email' => $user->email,
        ], $data ?? []);

        $subject = $template->subject;
        $body = $template->render($variables);

        Mail::raw($body, function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->subject($subject)
                ->html(true);
        });
    }

    /**
     * Send SMS action
     */
    protected function sendSms($action, $user, $data)
    {
        $message = $action['message'] ?? '';

        // Replace variables in message
        foreach ($data ?? [] as $key => $value) {
            $message = str_replace("{{" . $key . "}}", $value, $message);
        }

        // TODO: Integrate with SMS service (Twilio, etc.)
        Log::info("SMS would be sent", [
            'user_id' => $user->id,
            'message' => $message,
        ]);
    }

    /**
     * Add tag action
     */
    protected function addTag($action, $user)
    {
        $tag = $action['tag'] ?? null;

        if ($tag) {
            // TODO: Implement user tagging system
            Log::info("Tag added to user", [
                'user_id' => $user->id,
                'tag' => $tag,
            ]);
        }
    }

    /**
     * Update field action
     */
    protected function updateField($action, $user)
    {
        $field = $action['field'] ?? null;
        $value = $action['value'] ?? null;

        if ($field && $user->isFillable($field)) {
            $user->update([$field => $value]);
        }
    }

    /**
     * Trigger webhook action
     */
    protected function triggerWebhook($action, $user, $data)
    {
        $url = $action['url'] ?? null;

        if ($url) {
            $payload = [
                'user' => $user->toArray(),
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ];

            // TODO: Send webhook
            Log::info("Webhook triggered", [
                'url' => $url,
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        $this->execution->markAsFailed($exception->getMessage());

        Log::error("ProcessAutomationRule job failed", [
            'execution_id' => $this->execution->id,
            'rule_id' => $this->execution->rule_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
