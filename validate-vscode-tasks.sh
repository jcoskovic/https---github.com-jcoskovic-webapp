#!/bin/bash

# Abbrevio VS Code Tasks Validation Script
# This script validates that all the VS Code tasks are properly configured

echo "🔍 Validating VS Code Tasks Configuration for GitHub Copilot Agent..."

# Check if .vscode directory exists
if [ ! -d ".vscode" ]; then
    echo "❌ .vscode directory not found"
    exit 1
fi

# Check if tasks.json exists
if [ ! -f ".vscode/tasks.json" ]; then
    echo "❌ tasks.json not found"
    exit 1
fi

# Validate JSON syntax
if ! python3 -m json.tool .vscode/tasks.json > /dev/null 2>&1; then
    echo "❌ tasks.json has invalid JSON syntax"
    exit 1
fi

echo "✅ tasks.json is valid JSON"

# Check for required tasks
required_tasks=(
    "Start Full Stack Application"
    "Setup Development Environment"
    "Run All Tests"
    "Quick Health Check"
    "Frontend: Start Development Server"
    "Backend: Start Development Server"
    "ML Service: Start Development Server"
)

echo "🔍 Checking for required tasks..."

for task in "${required_tasks[@]}"; do
    if grep -q "\"label\": \"$task\"" .vscode/tasks.json; then
        echo "✅ Found task: $task"
    else
        echo "❌ Missing task: $task"
        exit 1
    fi
done

# Check if launch.json exists and is valid
if [ -f ".vscode/launch.json" ]; then
    if python3 -m json.tool .vscode/launch.json > /dev/null 2>&1; then
        echo "✅ launch.json is valid JSON"
    else
        echo "❌ launch.json has invalid JSON syntax"
        exit 1
    fi
else
    echo "⚠️  launch.json not found (optional)"
fi

# Check if settings.json exists and is valid
if [ -f ".vscode/settings.json" ]; then
    if python3 -m json.tool .vscode/settings.json > /dev/null 2>&1; then
        echo "✅ settings.json is valid JSON"
    else
        echo "❌ settings.json has invalid JSON syntax"
        exit 1
    fi
else
    echo "⚠️  settings.json not found (optional)"
fi

# Check Docker availability
if command -v docker > /dev/null 2>&1; then
    echo "✅ Docker is available"
    
    if docker compose --help > /dev/null 2>&1; then
        echo "✅ Docker Compose is available"
    else
        echo "❌ Docker Compose is not available"
        exit 1
    fi
else
    echo "❌ Docker is not available"
    exit 1
fi

# Check if docker-compose.yml exists
if [ -f "docker-compose.yml" ]; then
    echo "✅ docker-compose.yml found"
else
    echo "❌ docker-compose.yml not found"
    exit 1
fi

# Check required directories
required_dirs=("frontend" "backend" "ml-service")

for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        echo "✅ Found directory: $dir"
    else
        echo "❌ Missing directory: $dir"
        exit 1
    fi
done

# Check for package managers
if [ -f "frontend/package.json" ]; then
    echo "✅ Frontend package.json found"
else
    echo "❌ Frontend package.json not found"
    exit 1
fi

if [ -f "backend/composer.json" ]; then
    echo "✅ Backend composer.json found"
else
    echo "❌ Backend composer.json not found"
    exit 1
fi

if [ -f "ml-service/requirements.txt" ]; then
    echo "✅ ML Service requirements.txt found"
else
    echo "❌ ML Service requirements.txt not found"
    exit 1
fi

echo ""
echo "🎉 All VS Code tasks validation checks passed!"
echo ""
echo "📋 Summary:"
echo "  - Tasks configuration: ✅ Valid"
echo "  - Docker environment: ✅ Ready" 
echo "  - Project structure: ✅ Complete"
echo ""
echo "🤖 GitHub Copilot agents can now:"
echo "  - Automatically start the full stack application"
echo "  - Run individual component servers"
echo "  - Execute all tests suites"
echo "  - Perform health checks"
echo "  - Setup development environment"
echo ""
echo "💡 To start the application, run:"
echo "   Cmd+Shift+P → 'Tasks: Run Task' → 'Start Full Stack Application'"
