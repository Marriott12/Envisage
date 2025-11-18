# Node.js Installation Guide for Envisage

## ✅ BACKEND IS WORKING PERFECTLY!

Your backend API is fully operational and responding correctly:
```json
{
  "name": "Envisage E-Commerce API",
  "version": "1.0.0",
  "status": "online",
  "endpoints": {
    "health_check": "http://127.0.0.1:8000/api/test",
    "products": "http://127.0.0.1:8000/api/products",
    "authentication": "http://127.0.0.1:8000/api/login"
  }
}
```

## 📥 Install Node.js - Choose ONE Method:

### Method 1: Direct Download (RECOMMENDED - Easiest)

1. **Open your browser** and visit: **https://nodejs.org/**

2. **Download the LTS version** (should be v20.x or v18.x)
   - Click the big green button that says "LTS (Recommended For Most Users)"
   - This will download a file like: `node-v20.x.x-x64.msi`

3. **Run the installer**
   - Double-click the downloaded `.msi` file
   - Click "Next" through the installer
   - ✅ **IMPORTANT:** Make sure "Add to PATH" is checked (it's usually checked by default)
   - Complete the installation

4. **Restart VS Code completely**
   - Close all VS Code windows
   - Reopen VS Code

5. **Verify installation** (in a new terminal):
   ```powershell
   node --version
   npm --version
   ```

---

### Method 2: Using PowerShell (Admin Required)

If you have winget available:

```powershell
# Open PowerShell as Administrator
# Then run:
winget install OpenJS.NodeJS.LTS
```

---

### Method 3: Using Chocolatey (If Installed)

If you have Chocolatey package manager:

```powershell
# Open PowerShell as Administrator
choco install nodejs-lts -y
```

---

## 🚀 After Node.js is Installed

Open a **NEW terminal** in VS Code (important for PATH to refresh), then run:

```powershell
# Verify Node.js is installed
node --version
npm --version

# Navigate to frontend
cd c:\wamp64\www\Envisage\frontend

# Create environment file
Copy-Item .env.local.example .env.local

# Install dependencies (this will take 2-3 minutes)
npm install

# Start frontend development server
npm run dev
```

The frontend will be available at: **http://localhost:3000**

---

## 🎯 Quick Start (Copy-Paste After Node.js Install)

```powershell
# Step 1: Verify
node --version

# Step 2: Setup frontend
cd c:\wamp64\www\Envisage\frontend
Copy-Item .env.local.example .env.local

# Step 3: Install & Run
npm install
npm run dev
```

---

## 🔧 Expected Outcome

After `npm run dev`, you should see:
```
✓ Ready in 3.2s
○ Local:    http://localhost:3000
```

Then visit http://localhost:3000 in your browser!

---

## 📋 Complete Stack Status

| Component | Status | URL |
|-----------|--------|-----|
| **Backend API** | ✅ **RUNNING** | http://127.0.0.1:8000 |
| **Database** | ✅ **READY** | envisage_db |
| **Frontend** | ⏳ **NEEDS NODE.JS** | http://localhost:3000 |

---

## 💡 Troubleshooting

**If `node --version` still doesn't work after install:**
1. Close ALL VS Code windows
2. Close ALL terminal/PowerShell windows  
3. Reopen VS Code
4. Open a NEW terminal (Ctrl+`)
5. Try `node --version` again

**If npm install fails:**
```powershell
# Try with legacy peer deps
npm install --legacy-peer-deps
```

---

## 🎉 You're Almost Done!

1. Download Node.js: https://nodejs.org/
2. Install it (keep "Add to PATH" checked)
3. Restart VS Code
4. Run the 3 commands above
5. Visit http://localhost:3000

That's it! 🚀
