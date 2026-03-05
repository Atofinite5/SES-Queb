# SES-Queb

**Secure Ecosystem Scaffolder & Dependency Auditor**

A production-ready Laravel API that scaffolds secure Node.js/React/Vue projects and audits existing projects for vulnerabilities, license compliance, and security misconfigurations.

---

## 🎯 What It Does

- **🚀 Generate Projects** → Create secure, production-ready React, Vue, or Express projects with best practices
- **🔒 Scan Security** → Detect npm vulnerabilities, outdated packages, hardcoded secrets, and misconfigurations
- **📜 Check Licenses** → Verify dependency licenses for compatibility and legal risks
- **🔗 Push to GitHub** → Create repositories and push generated projects directly to GitHub
- **🤖 AI Suggestions** → Get intelligent fix recommendations via OpenAI integration

---

## 📋 Requirements

- **PHP** 8.2+
- **Composer** (dependency manager)
- **PostgreSQL** database
- **Node.js** (npm/yarn/pnpm - for project generation)
- **Git** (for GitHub integration)

---

## 🚀 Quick Start

### 1. **Install & Setup**

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/ses-queb.git
cd ses-queb

# Install PHP dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed --class=TemplateSeeder
```

### 2. **Run Locally**

```bash
php artisan serve
```

API available at: `http://localhost:8000/api/v1`

---

## 📡 API Endpoints

### Templates

```bash
GET  /api/v1/templates              # List all templates
GET  /api/v1/templates/{id}         # Get template details
```

### Scaffold (Project Generation)

```bash
POST /api/v1/scaffold               # Create new project
GET  /api/v1/scaffold/{jobId}/status    # Check generation status
GET  /api/v1/scaffold/{jobId}/download  # Download generated project
```

### Audit (Security Scanning)

```bash
POST /api/v1/audit                  # Run security audit
GET  /api/v1/audit/{reportId}       # Get audit report
GET  /api/v1/audits                 # List all audits
```

### Configurations

```bash
GET  /api/v1/configs                # List saved configurations
POST /api/v1/configs                # Save configuration
GET  /api/v1/configs/{id}           # Get configuration
DELETE /api/v1/configs/{id}         # Delete configuration
```

### GitHub Integration

```bash
POST /api/v1/github/connect         # Connect GitHub account
POST /api/v1/github/push            # Push project to GitHub
GET  /api/v1/github/repositories    # List user repositories
```

---

## 📖 Example Usage

### Create a React Project

```bash
curl -X POST http://localhost:8000/api/v1/scaffold \
  -H "Content-Type: application/json" \
  -d '{
    "template_id": 1,
    "name": "my-react-app",
    "config": {
      "typescript": true,
      "linting": true
    }
  }'
```

### Run Security Audit

```bash
curl -X POST http://localhost:8000/api/v1/audit \
  -H "Content-Type: application/json" \
  -d '{
    "project_path": "/path/to/project",
    "audit_type": "full"
  }'
```

### List Available Templates

```bash
curl http://localhost:8000/api/v1/templates
```

---

## ⚙️ Configuration

Edit `.env` to configure:

```env
APP_NAME=SES-Queb
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ses_queb
DB_USERNAME=postgres
DB_PASSWORD=

# Optional: GitHub OAuth
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_secret

# Optional: AI-powered fixes
AI_API_KEY=sk-your_openai_api_key
```

---

## 🚀 Deployment

### Deploy on Render (Free 24/7)

1. Push code to GitHub
2. Go to [Render.com](https://render.com)
3. Create new Web Service
4. Connect your `ses-queb` GitHub repository
5. Set environment variables
6. Deploy ✅

**Time:** ~10 minutes | **Cost:** $0/month

See [RENDER_FREE_DEPLOY.md](./RENDER_FREE_DEPLOY.md) for detailed guide.

---

## 📚 File Structure

```text
ses-queb/
├── app/
│   ├── Http/Controllers/Api/      # API controllers
│   ├── Services/                   # Business logic
│   ├── Models/                     # Database models
│   └── Enums/                      # Status enums
├── config/
│   └── securescaffold.php          # Custom configuration
├── database/
│   ├── migrations/                 # Database schema
│   └── seeders/                    # Sample data
├── routes/
│   └── api.php                     # API routes
├── .env.example                    # Environment template
└── README.md                       # This file
```

---

## 🔒 Security Features

- ✅ Input validation on all endpoints
- ✅ Token encryption for sensitive data
- ✅ Vulnerability scanning (npm audit)
- ✅ Secret detection (hardcoded keys)
- ✅ License compliance checking
- ✅ Error handling with proper HTTP status codes
- ✅ Comprehensive logging

---

## 📞 Support

- **Documentation:** See `/memory` folder for detailed guides
- **Issues:** Create a GitHub issue for bugs or feature requests
- **API Docs:** Visit `/api/v1` after starting server

---

## 📝 License

MIT License - See LICENSE file for details

---

**Status:** Production Ready ✅
**Version:** 1.0.0
**Last Updated:** March 2026
