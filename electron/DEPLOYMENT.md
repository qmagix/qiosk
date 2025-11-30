# Deployment Instructions

This document outlines how to build and deploy the EyePub Electron Player.

## Prerequisites

- Node.js (v18 or higher)
- NPM
- Git

## Building the Application

The build process consists of two main steps:
1.  Building the frontend (Vite/Vue)
2.  Packaging the Electron app

### 1. Build Frontend

First, ensure the frontend is built with the correct base path for Electron.

```bash
cd frontend
npm install
npm run build:electron
```

This will create the production assets in `frontend/dist`.

### 2. Package Electron App

Navigate to the electron directory and run the distribution script.

```bash
cd electron
npm install
```

**For macOS:**
```bash
npm run dist:mac
```
Output: `electron/dist/EyePub Player-1.0.0.dmg` (and .zip)

**For Windows:**
(Note: It is best to build Windows apps on Windows, but simple builds can sometimes be done from Mac/Linux using Wine, though electron-builder handles some of this. For best results, run this on a Windows machine or CI/CD).
```bash
npm run dist:win
```
Output: `electron/dist/EyePub Player Setup 1.0.0.exe`

**For Linux:**
```bash
npm run dist:linux
```
Output: `electron/dist/EyePub Player-1.0.0.AppImage`

## Automated Build Script

You can use the `prebuild` script automatically included in the `dist` commands, which copies the `frontend/dist` assets into `electron/renderer` before packaging.

## Installation

### macOS
1.  Open the `.dmg` file.
2.  Drag "EyePub Player" to the Applications folder.
3.  Open the application.
    *   *Note*: Since the app is not code-signed with an Apple Developer Certificate, you may need to right-click and select "Open" the first time, or go to System Settings > Privacy & Security to allow it.

### Windows
1.  Run the `.exe` installer.
2.  The application will install and launch automatically.

### Linux
1.  Make the `.AppImage` executable: `chmod +x EyePub\ Player-1.0.0.AppImage`
2.  Run it: `./EyePub\ Player-1.0.0.AppImage`

## Code Signing (Production)

To avoid security warnings (especially on macOS and Windows), you need to sign your application.

**macOS:**
1.  Enroll in the Apple Developer Program.
2.  Generate a "Developer ID Application" certificate.
3.  Configure `electron-builder` with your identity.

**Windows:**
1.  Purchase a Code Signing Certificate (EV is recommended).
2.  Configure `electron-builder` with the certificate path and password.

Refer to [electron-builder code signing documentation](https://www.electron.build/code-signing) for details.
