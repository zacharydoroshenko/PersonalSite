#!/bin/bash

# Loop through every .cpp file in the current directory
for file in *.cpp; do
    # Extract the filename without the extension (e.g., "echo-cpp")
    filename="${file%.*}"
    
    echo "Compiling $file..."
    
    # Compile the file into a .cgi executable
    g++ "$file" -o "$filename.cgi"
    
    # Check if compilation succeeded
    if [ $? -eq 0 ]; then
        # Set execution permissions immediately
        chmod +x "$filename.cgi"
        echo "Successfully created $filename.cgi"
    else
        echo "Error: Failed to compile $file"
    fi
done

echo "--- Build Process Complete ---"