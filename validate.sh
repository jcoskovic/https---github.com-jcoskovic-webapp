#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔍 Running Abbrevio Project Validation...${NC}\n"

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
        return 1
    fi
}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}❌ Please run this script from the project root directory${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Backend Validation${NC}"
echo "================================"

# Check if composer exists
if ! command_exists composer; then
    echo -e "${RED}❌ Composer not found. Please install Composer first.${NC}"
    exit 1
fi

# Validate composer.json
cd backend

# Ensure Laravel directories exist
echo "Creating Laravel directories..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
mkdir -p storage/app/public

# Set permissions
chmod -R 775 bootstrap/cache storage 2>/dev/null || echo -e "${YELLOW}⚠️ Could not set directory permissions (might be running on Windows)${NC}"

composer validate --strict
print_status $? "composer.json validation"

# Check composer dependencies
if [ -f "composer.lock" ]; then
    composer check-platform-reqs
    print_status $? "Platform requirements check"
else
    echo -e "${YELLOW}⚠️ composer.lock not found. Run 'composer install' first.${NC}"
fi

cd ..

echo -e "\n${YELLOW}🌐 Frontend Validation${NC}"
echo "================================"

# Check if npm exists
if command_exists npm; then
    cd frontend
    if [ -f "package.json" ]; then
        npm audit --audit-level moderate
        print_status $? "NPM security audit"
        
        if [ -f "package-lock.json" ]; then
            echo -e "${GREEN}✅ package-lock.json exists${NC}"
        else
            echo -e "${YELLOW}⚠️ package-lock.json not found. Run 'npm install' first.${NC}"
        fi
        
        # Run ESLint check
        echo -e "\n${YELLOW}🔍 Running ESLint validation...${NC}"
        npm run lint >/dev/null 2>&1
        exit_code=$?
        if [ $exit_code -eq 0 ]; then
            echo -e "${GREEN}✅ ESLint validation passed (no errors)${NC}"
        else
            # Check if there are only warnings (exit code 1 for warnings in some configs)
            warning_count=$(npm run lint 2>&1 | grep -c "warning")
            error_count=$(npm run lint 2>&1 | grep -c "error" | grep -v "warning")
            if [ $error_count -eq 0 ] && [ $warning_count -gt 0 ]; then
                echo -e "${YELLOW}⚠️ ESLint passed with $warning_count warnings (no blocking errors)${NC}"
            else
                echo -e "${RED}❌ ESLint validation failed${NC}"
            fi
        fi
    else
        echo -e "${RED}❌ package.json not found in frontend directory${NC}"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠️ npm not found. Skipping frontend validation.${NC}"
fi

echo -e "\n${YELLOW}🐍 ML Service Validation${NC}"
echo "================================"

# Check if python exists
if command_exists python3; then
    cd ml-service
    if [ -f "requirements.txt" ]; then
        # Check if virtual environment should be used
        if [ -d "venv" ]; then
            echo -e "${GREEN}✅ Virtual environment found${NC}"
        else
            echo -e "${YELLOW}⚠️ No virtual environment found. Consider creating one.${NC}"
        fi
        
        # Validate Python syntax for main file
        python3 -m py_compile app.py
        print_status $? "Python syntax validation"
    else
        echo -e "${RED}❌ requirements.txt not found in ml-service directory${NC}"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠️ python3 not found. Skipping ML service validation.${NC}"
fi

echo -e "\n${YELLOW}🐳 Docker Validation${NC}"
echo "================================"

# Check if docker exists
if command_exists docker; then
    # Validate docker-compose.yml syntax
    docker-compose config >/dev/null 2>&1
    print_status $? "docker-compose.yml syntax"
    
    # Check if Docker daemon is running
    docker info >/dev/null 2>&1
    print_status $? "Docker daemon status"
else
    echo -e "${YELLOW}⚠️ Docker not found. Skipping Docker validation.${NC}"
fi

echo -e "\n${YELLOW}📁 Project Structure Validation${NC}"
echo "================================"

# Check essential files
essential_files=(
    "README.md"
    "docker-compose.yml"
    "backend/composer.json"
    "frontend/package.json"
    "ml-service/requirements.txt"
    "ml-service/app.py"
)

for file in "${essential_files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅ $file${NC}"
    else
        echo -e "${RED}❌ $file${NC}"
    fi
done

# Check for common development files
dev_files=(
    "backend/.env.example"
    "backend/.env.testing"
    ".gitignore"
    ".github/workflows/ci-cd.yml"
)

echo -e "\n${YELLOW}🔧 Development Files${NC}"
for file in "${dev_files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅ $file${NC}"
    else
        echo -e "${YELLOW}⚠️ $file${NC}"
    fi
done

echo -e "\n${GREEN}🎉 Validation complete!${NC}"
echo -e "\n${YELLOW}💡 Tips:${NC}"
echo "• Run 'docker-compose up -d' to start all services"
echo "• Check http://localhost:4200 for frontend"
echo "• Check http://localhost:8000 for backend API"
echo "• Check http://localhost:5001 for ML service"

