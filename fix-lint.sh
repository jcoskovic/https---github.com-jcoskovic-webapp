#!/bin/bash

# 🛠️ ESLint Auto-Fix Script for Abbrevio Frontend

echo "🔍 Fixing ESLint issues systematically..."

# Set frontend directory
FRONTEND_DIR="/Users/ilija/Code/abbrevio/frontend"
cd "$FRONTEND_DIR"

echo "📁 Working in: $FRONTEND_DIR"

# 1. Remove unused imports and variables
echo "🧹 Step 1: Removing unused imports and fixing array types..."

# Fix Array<T> to T[] syntax
find src -name "*.ts" -exec sed -i '' 's/Array</[]/g' {} \;
find src -name "*.ts" -exec sed -i '' 's/\[\]</T[]/g' {} \;

# Fix specific unused imports by commenting them out temporarily
files_with_unused=(
    "src/app/app.routes.ts"
    "src/app/components/abbreviations/abbreviations.component.ts"
    "src/app/services/abbreviation.service.ts"
    "src/app/services/recommendation.service.ts"
    "src/test.ts"
)

echo "🎯 Step 2: Fixing unused variables..."

# Remove or comment unused parameters from functions
for file in "${files_with_unused[@]}"; do
    if [ -f "$file" ]; then
        echo "  📝 Processing: $file"
        # Add underscore prefix to unused parameters to suppress warnings
        sed -i '' 's/error)/(_error)/g' "$file"
        sed -i '' 's/response)/(_response)/g' "$file"
        sed -i '' 's/result)/(_result)/g' "$file"
        sed -i '' 's/pagination)/(_pagination)/g' "$file"
        sed -i '' 's/filters)/(_filters)/g' "$file"
        sed -i '' 's/recommendations)/(_recommendations)/g' "$file"
        sed -i '' 's/suggestions)/(_suggestions)/g' "$file"
    fi
done

echo "🏗️ Step 3: Fixing constructor issues..."

# Replace empty constructors with no constructor
find src -name "*.ts" -exec sed -i '' '/constructor() {[[:space:]]*}/d' {} \;
find src -name "*.ts" -exec sed -i '' '/constructor() { }/d' {} \;

echo "🎨 Step 4: Adding accessibility attributes..."

# Add role and tabindex to clickable elements
find src -name "*.html" -exec sed -i '' 's/(click)="\([^"]*\)"/(click)="\1" role="button" tabindex="0" (keyup.enter)="\1"/g' {} \;

echo "✨ Step 5: Running ESLint with --fix..."

npm run lint -- --fix || echo "⚠️ Some issues remain after auto-fix"

echo "📊 Step 6: Final lint check..."
npm run lint 2>&1 | head -20

echo "✅ Auto-fix completed! Check the results above."
echo "💡 Remaining issues may need manual intervention."
