# CLAUDE.md - AI Assistant Guide

> **Last Updated**: 2026-01-08
> **Repository**: book-now
> **Status**: 🟡 Initial Setup Phase

This document serves as a comprehensive guide for AI assistants (like Claude) working with this codebase. It provides essential context, conventions, and workflows to ensure consistent and effective collaboration.

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Repository State](#repository-state)
3. [Codebase Structure](#codebase-structure)
4. [Development Workflow](#development-workflow)
5. [Coding Conventions](#coding-conventions)
6. [Git Practices](#git-practices)
7. [Testing Strategy](#testing-strategy)
8. [Common Tasks](#common-tasks)
9. [AI Assistant Guidelines](#ai-assistant-guidelines)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

### Current State
**book-now** is a newly initialized repository currently in the initial setup phase. The project structure and technology stack are yet to be established.

### Project Goals
*To be documented as the project evolves*

### Technology Stack
*To be documented when dependencies are added*

### Key Dependencies
*None yet - repository is in initial setup phase*

---

## 📊 Repository State

### Current Branch Strategy
- **Main Branch**: TBD
- **Feature Branches**: Use `claude/*` prefix for AI-assisted development
- **Active Branch**: `claude/add-claude-documentation-s0a9J`

### Repository Metadata
- **Remote**: `http://local_proxy@127.0.0.1:59358/git/jcastillotx/book-now`
- **Owner**: Jeremiah Castillo (kre8ivtech@gmail.com)
- **Initial Commit**: 2026-01-08 09:30:20 -0600
- **Total Commits**: 1
- **Repository Size**: ~133KB (mostly .git directory)

### File Inventory
```
book-now/
├── .git/                    # Git configuration and objects
├── README.md               # Project readme (minimal placeholder)
└── CLAUDE.md              # This file - AI assistant guide
```

---

## 🏗️ Codebase Structure

### Current Structure
The repository is currently minimal with only foundational files. Structure will be documented as the project grows.

### Recommended Structure (Template for Future)
```
book-now/
├── src/                    # Source code
│   ├── components/         # Reusable components
│   ├── services/          # Business logic and services
│   ├── utils/             # Utility functions
│   └── config/            # Configuration files
├── tests/                 # Test files
├── docs/                  # Documentation
├── scripts/               # Build and utility scripts
├── public/                # Static assets (if applicable)
├── .github/               # GitHub-specific files
│   └── workflows/         # CI/CD workflows
├── .gitignore            # Git ignore patterns
├── README.md             # Project overview
├── CLAUDE.md             # This file
└── [config files]        # package.json, tsconfig.json, etc.
```

### Key Directories
*To be documented as directories are created*

### Important Files
- **README.md**: Project overview and setup instructions
- **CLAUDE.md**: This file - AI assistant documentation

---

## 🔄 Development Workflow

### Branch Naming Conventions
- **Feature branches**: `claude/<description>-<session-id>`
  - Example: `claude/add-authentication-s0a9J`
- **Bug fixes**: `claude/fix-<issue>-<session-id>`
- **Documentation**: `claude/docs-<topic>-<session-id>`

### Standard Workflow
1. **Start on the correct branch** (usually provided in task context)
2. **Explore the codebase** to understand existing patterns
3. **Plan the implementation** (use TodoWrite for complex tasks)
4. **Implement changes** following established conventions
5. **Test your changes** (when testing infrastructure exists)
6. **Commit with clear messages** (see Git Practices below)
7. **Push to the feature branch** using `git push -u origin <branch-name>`

### Push/Pull Practices
- **Push**: Always use `git push -u origin <branch-name>`
- **Critical**: Branch must start with `claude/` and match session ID
- **Retry Logic**: If network errors occur, retry up to 4 times with exponential backoff (2s, 4s, 8s, 16s)
- **Fetch**: Prefer fetching specific branches: `git fetch origin <branch-name>`
- **Pull**: Use `git pull origin <branch-name>`

---

## 📝 Coding Conventions

### General Principles
1. **Consistency**: Follow existing patterns in the codebase
2. **Simplicity**: Avoid over-engineering; keep solutions focused
3. **Security**: Watch for common vulnerabilities (XSS, SQL injection, command injection, etc.)
4. **No unnecessary changes**: Only modify what's needed for the task

### Code Style
*To be documented when source code is added*

### Naming Conventions
*To be documented based on language/framework choice*

### File Organization
*To be documented as project structure develops*

### Documentation Standards
- **Inline Comments**: Only where logic isn't self-evident
- **Function Documentation**: Document complex functions and public APIs
- **README Updates**: Keep README.md current with setup instructions
- **CLAUDE.md Updates**: Update this file when workflows or conventions change

---

## 🔧 Git Practices

### Commit Message Format
```
<type>: <short summary>

<optional detailed description>

<optional footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `style`: Code style changes (formatting, etc.)

**Examples:**
```bash
feat: add user authentication system

Implement JWT-based authentication with login and registration endpoints.
Includes password hashing and token validation middleware.

fix: resolve booking date validation bug

Update date comparison logic to handle timezone offsets correctly.

docs: update CLAUDE.md with new conventions
```

### Commit Best Practices
- **Atomic commits**: Each commit should represent one logical change
- **Clear messages**: Focus on the "why" rather than the "what"
- **No secrets**: Never commit sensitive data (.env files, API keys, credentials)
- **Staged changes**: Review changes before committing (`git status`, `git diff`)
- **Hook compliance**: Respect pre-commit hooks and linters

### Branch Management
- **Never force push** to main/master without explicit permission
- **Clean history**: Use meaningful commit messages
- **Branch lifecycle**: Delete feature branches after merging (when applicable)

---

## 🧪 Testing Strategy

### Current State
*No testing infrastructure exists yet*

### Recommended Testing Approach (For Future)
- **Unit Tests**: Test individual functions and components
- **Integration Tests**: Test interactions between modules
- **End-to-End Tests**: Test complete user workflows
- **Test Location**: Co-locate tests with source or use `/tests` directory

### Running Tests
*To be documented when testing is implemented*

---

## ⚡ Common Tasks

### Setting Up Development Environment
*To be documented when project dependencies are added*

```bash
# Placeholder for future setup commands
# Example: npm install, pip install -r requirements.txt, etc.
```

### Building the Project
*To be documented when build process is established*

### Running the Application
*To be documented when application code exists*

### Common Commands
*To be documented based on project tooling*

---

## 🤖 AI Assistant Guidelines

### Before Making Changes
1. ✅ **Read files first**: Never propose changes to code you haven't read
2. ✅ **Understand context**: Explore related files to understand patterns
3. ✅ **Check conventions**: Follow existing code style and organization
4. ✅ **Plan complex tasks**: Use TodoWrite for multi-step implementations

### During Implementation
1. ✅ **Use TodoWrite**: Track progress for complex tasks (3+ steps)
2. ✅ **Follow patterns**: Match existing code structure and conventions
3. ✅ **Avoid over-engineering**: Don't add unnecessary features or abstractions
4. ✅ **Security focus**: Watch for vulnerabilities (OWASP Top 10)
5. ✅ **Minimal changes**: Only change what's necessary for the task

### What NOT to Do
1. ❌ **Don't add unnecessary features**: Stick to the requested changes
2. ❌ **Don't refactor unrelated code**: Avoid "improvements" beyond the task
3. ❌ **Don't add comments everywhere**: Only where logic isn't clear
4. ❌ **Don't create premature abstractions**: Three similar lines > complex abstraction
5. ❌ **Don't add error handling for impossible scenarios**: Trust internal code
6. ❌ **Don't create documentation files unprompted**: Only when explicitly requested
7. ❌ **Don't use emojis**: Unless explicitly requested by the user

### After Implementation
1. ✅ **Test your changes**: Verify functionality when testing exists
2. ✅ **Commit with clear messages**: Follow commit message format
3. ✅ **Update documentation**: If you changed workflows or added features
4. ✅ **Mark todos complete**: Keep TodoWrite list current

### Tool Usage Best Practices
- **Parallel operations**: Use multiple tool calls when operations are independent
- **Specialized tools**: Prefer Read/Edit/Write over bash commands for files
- **Task tool**: Use for open-ended exploration or complex searches
- **Direct communication**: Output text directly; never use bash echo to communicate

### Code References
When referencing code, use the pattern: `file_path:line_number`

Example: "The authentication logic is in `src/auth/login.js:45`"

---

## 🔍 Troubleshooting

### Common Issues

#### Git Push Fails with 403
- **Cause**: Branch name doesn't match required pattern
- **Solution**: Ensure branch starts with `claude/` and includes session ID
- **Example**: `claude/add-feature-s0a9J`

#### Network Errors During Git Operations
- **Solution**: Retry with exponential backoff (2s, 4s, 8s, 16s)
- **Implementation**: Built into workflow; automatic retries

#### Pre-commit Hook Failures
- **Solution**: Review hook feedback and adjust changes accordingly
- **Tip**: Check `.git/hooks/` for hook configurations

### Getting Help
- Check project README.md for setup instructions
- Review recent commit messages for context
- Explore similar files to understand patterns
- Ask the user for clarification when requirements are unclear

---

## 📚 Additional Resources

### Project Documentation
- **README.md**: Project overview and setup
- **This file (CLAUDE.md)**: AI assistant guide

### External Documentation
*To be added as external dependencies and services are integrated*

---

## 🔄 Maintaining This Document

### When to Update CLAUDE.md
- **Technology changes**: New languages, frameworks, or tools added
- **Workflow changes**: New development processes or conventions
- **Structure changes**: Significant reorganization of codebase
- **Convention changes**: New coding standards or practices
- **Tooling changes**: New build tools, testing frameworks, or CI/CD

### Update Process
1. Make changes to reflect current state
2. Update "Last Updated" date at top of file
3. Document what changed in commit message
4. Keep document organized and scannable

### Document Ownership
This document is collaboratively maintained by:
- **Project team**: Define conventions and workflows
- **AI assistants**: Document patterns discovered during development
- **Contributors**: Update based on experience and feedback

---

## ✨ Summary for Quick Reference

### Key Points
- 🟡 **Status**: Repository is in initial setup phase
- 🌿 **Branches**: Use `claude/*` prefix for AI-assisted work
- 📝 **Commits**: Clear, atomic commits with descriptive messages
- 🔐 **Security**: Watch for vulnerabilities; never commit secrets
- 🎯 **Focus**: Make only necessary changes; avoid over-engineering
- 📋 **Planning**: Use TodoWrite for complex tasks (3+ steps)
- 🤝 **Consistency**: Follow existing patterns and conventions

### Quick Commands
```bash
# Check current status
git status

# View recent commits
git log --oneline -10

# View changes
git diff

# Commit changes
git add .
git commit -m "type: clear description"

# Push to feature branch
git push -u origin <branch-name>
```

---

**Remember**: This document evolves with the project. Keep it updated to remain a valuable resource for AI assistants and human developers alike.

---

*Generated by Claude - AI Assistant Documentation Tool*
*Repository: book-now*
*Version: 1.0.0*
*Date: 2026-01-08*
