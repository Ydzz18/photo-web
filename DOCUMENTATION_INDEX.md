# Documentation Index

Complete guide to all LensCraft documentation files.

---

## 📚 Documentation Structure

All documentation files are organized by purpose. Start with the appropriate section for your needs.

---

## 👥 For Users

**Start Here if you're a user or need help using LensCraft**

### 1. **[QUICK_START.md](QUICK_START.md)** ⭐ START HERE
- **Time:** 5 minutes
- **Contents:**
  - Step-by-step setup in 5 minutes
  - Feature overview table
  - Security tips
  - Next steps
- **Best for:** First-time users who need to get started quickly

### 2. **[USER_GUIDE.md](USER_GUIDE.md)** 📖 COMPREHENSIVE
- **Time:** 30-45 minutes
- **Contents:**
  - Complete feature walkthrough
  - Registration and login
  - Photo management
  - Social features (likes, comments, follows)
  - Profile management
  - Settings and preferences
  - Search and discovery
  - FAQ section
  - Troubleshooting guide
  - Best practices
- **Best for:** Users who need detailed help with any feature

---

## 👨‍💻 For Developers

**Start Here if you're developing or maintaining LensCraft**

### Phase 1: Understanding the System

#### 1. **[README.md](README.md)** ⭐ START HERE
- **Time:** 10-15 minutes
- **Contents:**
  - Project overview
  - Feature list
  - Technology stack
  - Installation instructions
  - Project structure
  - Development setup
  - Common development tasks
  - Testing checklist
- **Best for:** New developers getting familiar with the project

#### 2. **[TECHNICAL_ARCHITECTURE.md](TECHNICAL_ARCHITECTURE.md)** 🏗️
- **Time:** 20-30 minutes
- **Contents:**
  - System overview and architecture diagrams
  - Application layers (presentation, business, data)
  - Technology stack details
  - Complete directory structure
  - Request flow diagrams
  - Authentication & security flows
  - Email system architecture
  - Performance optimization
  - Error handling strategies
  - Deployment checklist
- **Best for:** Understanding how the system works architecturally

### Phase 2: Learning the APIs and Data

#### 3. **[API_REFERENCE.md](API_REFERENCE.md)** 🔌
- **Time:** 30-45 minutes
- **Contents:**
  - Authentication system
  - Standard response format
  - All AJAX endpoints with examples
  - All form endpoints with examples
  - Error handling and responses
  - Rate limiting recommendations
  - Best practices and code examples
  - JavaScript fetch examples
- **Best for:** Integrating with APIs or building new endpoints

#### 4. **[DATA_MODELS.md](DATA_MODELS.md)** 📊
- **Time:** 30-45 minutes
- **Contents:**
  - Database schema for all 12 tables
  - Column definitions and constraints
  - Sample records in JSON
  - Database relationships and ERD
  - Indexes and performance tips
  - Query examples
  - Backup/recovery procedures
  - Data integrity information
- **Best for:** Working with database or designing queries

### Phase 3: Working with Services and Code

#### 5. **[SERVICES_README.md](SERVICES_README.md)** ⚙️
- **Time:** 30-45 minutes
- **Contents:**
  - EmailService - send emails via SMTP
  - EmailConfirmationService - email verification
  - TwoFactorAuthService - 2FA codes
  - NotificationManager - user notifications
  - UserLogger - activity logging
  - SiteSettings - configuration management
  - All methods with examples
  - Best practices
- **Best for:** Using or extending service classes

#### 6. **[CODE_STYLE_GUIDE.md](CODE_STYLE_GUIDE.md)** 📝
- **Time:** 15-20 minutes
- **Contents:**
  - PHP coding standards
  - Naming conventions (variables, functions, classes)
  - Documentation standards (docstrings, comments)
  - Function and class guidelines
  - Database best practices
  - Security best practices
  - Error handling patterns
  - Code review checklist
- **Best for:** Writing code that follows project standards

### Phase 4: Configuration and Deployment

#### 7. **[CONFIG_README.md](CONFIG_README.md)** ⚙️
- **Time:** 20-30 minutes
- **Contents:**
  - Environment variables (.env setup)
  - Email configuration (Gmail SMTP)
  - Database configuration
  - Server configuration (Apache, PHP)
  - Security settings and permissions
  - Email templates
  - Admin configuration
  - Monitoring and logging
- **Best for:** Setting up and configuring the application

---

## 🔍 Quick Reference

### By Task

**I need to...**

| Task | Document | Section |
|------|----------|---------|
| Get started quickly | QUICK_START.md | - |
| Learn all features | USER_GUIDE.md | - |
| Understand architecture | TECHNICAL_ARCHITECTURE.md | - |
| Work with APIs | API_REFERENCE.md | - |
| Design database queries | DATA_MODELS.md | Query Examples |
| Use EmailService | SERVICES_README.md | EmailService |
| Use 2FA | SERVICES_README.md | TwoFactorAuthService |
| Create notifications | SERVICES_README.md | NotificationManager |
| Write code following standards | CODE_STYLE_GUIDE.md | - |
| Configure Gmail SMTP | CONFIG_README.md | Email Configuration |
| Configure database | CONFIG_README.md | Database Configuration |
| Deploy to production | README.md, CONFIG_README.md | Deployment sections |
| Fix email issues | CONFIG_README.md | Troubleshooting Email |
| Debug database errors | README.md | Troubleshooting |
| Find API endpoints | API_REFERENCE.md | AJAX/Form Endpoints |
| Understand data models | DATA_MODELS.md | - |

---

## 🛠️ Development Workflow

### 1. Setup (First Time)
1. Read: **README.md** (Installation Steps)
2. Read: **CONFIG_README.md** (Environment Setup)
3. Run setup commands
4. Verify with health-check.php

### 2. Understanding the System
1. Read: **TECHNICAL_ARCHITECTURE.md** (System Overview)
2. Review: **Project Structure** in README.md
3. Explore codebase with architecture in mind

### 3. Making Changes
1. Check: **CODE_STYLE_GUIDE.md** (follow standards)
2. Read: **API_REFERENCE.md** (if working with APIs)
3. Check: **DATA_MODELS.md** (if working with data)
4. Write code following guidelines
5. Add docstrings and comments
6. Test thoroughly

### 4. Adding Features
1. Read: **SERVICES_README.md** (use existing services)
2. Check: **DATA_MODELS.md** (understand data structure)
3. Review: **API_REFERENCE.md** (integration points)
4. Write code with proper documentation
5. Update documentation as needed

### 5. Deployment
1. Read: **README.md** (Production Checklist)
2. Read: **CONFIG_README.md** (Server Configuration)
3. Follow deployment steps
4. Run health checks
5. Monitor logs and activity

---

## 📋 File Organization

### User Documentation
```
photo-web/
├── QUICK_START.md              ← Quick getting started
└── USER_GUIDE.md               ← Comprehensive user manual
```

### Developer Documentation (High-Level)
```
photo-web/
├── README.md                   ← Project overview
├── TECHNICAL_ARCHITECTURE.md   ← System design
├── API_REFERENCE.md            ← API documentation
└── DATA_MODELS.md              ← Database schema
```

### Developer Documentation (Implementation)
```
photo-web/
├── SERVICES_README.md          ← Service classes
├── CODE_STYLE_GUIDE.md         ← Coding standards
├── CONFIG_README.md            ← Configuration
└── DOCUMENTATION_INDEX.md      ← This file
```

### Code Files with Documentation
```
photo-web/
├── db_connect.php              ← Documented: Database connection
├── config/EmailService.php     ← Documented: Email service
├── config/TwoFactorAuthService.php ← Documented: 2FA
├── notification_manager.php    ← Documented: Notifications
└── [other files]
```

---

## 🎯 Quick Navigation

### Core Concepts

**Authentication & Security**
- START: TECHNICAL_ARCHITECTURE.md → Authentication & Security
- LEARN: SERVICES_README.md → TwoFactorAuthService
- IMPLEMENT: CODE_STYLE_GUIDE.md → Security Best Practices
- CONFIGURE: CONFIG_README.md → Security Settings

**Email System**
- START: TECHNICAL_ARCHITECTURE.md → Email System
- LEARN: SERVICES_README.md → EmailService
- CONFIGURE: CONFIG_README.md → Email Configuration
- TROUBLESHOOT: CONFIG_README.md → Troubleshooting Email Issues

**Notifications**
- START: API_REFERENCE.md → Notification Management
- LEARN: SERVICES_README.md → NotificationManager
- UNDERSTAND: TECHNICAL_ARCHITECTURE.md → Request Flow

**Database**
- START: DATA_MODELS.md → Database Overview
- LEARN: TECHNICAL_ARCHITECTURE.md → Data Layer
- QUERY: DATA_MODELS.md → Query Examples
- OPTIMIZE: DATA_MODELS.md → Indexes & Performance

**APIs**
- START: API_REFERENCE.md → Overview
- UNDERSTAND: TECHNICAL_ARCHITECTURE.md → Request Flow
- EXAMPLES: API_REFERENCE.md → AJAX Endpoints
- IMPLEMENT: SERVICES_README.md → Service Classes

---

## 📖 Reading Order by Role

### New Developer
1. README.md
2. TECHNICAL_ARCHITECTURE.md
3. CODE_STYLE_GUIDE.md
4. SERVICES_README.md
5. API_REFERENCE.md
6. DATA_MODELS.md

### DevOps / System Admin
1. README.md (Installation)
2. CONFIG_README.md
3. TECHNICAL_ARCHITECTURE.md (Deployment)
4. README.md (Production Checklist)

### Feature Developer
1. CODE_STYLE_GUIDE.md
2. SERVICES_README.md
3. API_REFERENCE.md
4. DATA_MODELS.md
5. TECHNICAL_ARCHITECTURE.md

### Database Developer
1. DATA_MODELS.md
2. TECHNICAL_ARCHITECTURE.md (Data Layer)
3. API_REFERENCE.md
4. README.md (Troubleshooting)

### Technical Lead
1. README.md
2. TECHNICAL_ARCHITECTURE.md
3. CODE_STYLE_GUIDE.md
4. DATA_MODELS.md
5. API_REFERENCE.md

---

## 🔗 Cross-References

### Documentation Links

**README.md** references:
- USER_GUIDE.md - Full feature documentation
- TECHNICAL_ARCHITECTURE.md - System design
- API_REFERENCE.md - API endpoints
- DATA_MODELS.md - Database schema
- CONFIG_README.md - Configuration
- CODE_STYLE_GUIDE.md - Coding standards

**TECHNICAL_ARCHITECTURE.md** references:
- API_REFERENCE.md - Endpoint specifications
- DATA_MODELS.md - Database details
- CODE_STYLE_GUIDE.md - Code standards
- SERVICES_README.md - Service classes

**API_REFERENCE.md** references:
- SERVICES_README.md - Service implementation
- CODE_STYLE_GUIDE.md - Error handling
- DATA_MODELS.md - Data structures

---

## ✅ Documentation Checklist

- [x] User Guide (USER_GUIDE.md)
- [x] Quick Start (QUICK_START.md)
- [x] Technical Architecture (TECHNICAL_ARCHITECTURE.md)
- [x] API Reference (API_REFERENCE.md)
- [x] Data Models (DATA_MODELS.md)
- [x] Services (SERVICES_README.md)
- [x] Configuration (CONFIG_README.md)
- [x] Code Style Guide (CODE_STYLE_GUIDE.md)
- [x] Project README (README.md)
- [x] Documentation Index (this file)
- [x] Inline code comments (db_connect.php)
- [x] Docstrings in classes (services)

---

## 📞 Support & Questions

### Need Help Finding Something?

1. **Use the index above** - Quick Reference by Task
2. **Check Reading Order** - Based on your role
3. **Search within documents** - Use browser find (Ctrl+F)
4. **Review code comments** - Check inline comments for implementation details

### Document Feedback

If you find:
- ❌ Missing information
- 📝 Unclear explanation
- 🐛 Incorrect example
- 📖 Needs clarification

Please create an issue or update the documentation.

---

## 🚀 Getting Help

### Troubleshooting

**Email Issues:**
→ CONFIG_README.md → Troubleshooting Email Issues

**Database Errors:**
→ README.md → Troubleshooting → Database Connection Error

**Login Problems:**
→ README.md → Troubleshooting → Login Issues

**Photo Upload Failing:**
→ README.md → Troubleshooting → Photo Upload Failing

**API Integration:**
→ API_REFERENCE.md → Best Practices

### Feature Documentation

**Authentication:**
→ TECHNICAL_ARCHITECTURE.md → Authentication & Security

**Email System:**
→ TECHNICAL_ARCHITECTURE.md → Email System

**Social Features:**
→ API_REFERENCE.md → AJAX Endpoints

**Database:**
→ DATA_MODELS.md

---

**Last Updated:** December 2024  
**Documentation Version:** 1.0  
**Total Files:** 11  
**Total Pages:** 100+

---

**Navigation:** [README.md](README.md) | [USER_GUIDE.md](USER_GUIDE.md) | [QUICK_START.md](QUICK_START.md)
