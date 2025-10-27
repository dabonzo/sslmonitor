# Commit staged changes with proper message and push to all remote repositories

Usage: /commit [message]

Example: /commit docs: update documentation structure

If no message is provided, uses a default message based on staged changes.

Note: Commit messages follow project patterns without any attribution.
Pushes to both configured remotes:
- github (git@github.com:dabonzo/sslmonitor.git)
- origin (gitea:bonzo/ssl-monitor.git)