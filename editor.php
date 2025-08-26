<?php
session_start();
require_once 'config.php';

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoShop - Image Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .hidden {
            display: none !important;
        }
        
        body, html {
            height: 100%;
            overflow: hidden;
        }
        
        #historyContainer {
            overflow-y: auto;
            max-height: calc(100% - 70px); /* Account for header height */
        }
        
        #imageContainer {
            overflow: hidden; /* Changed from auto to hidden */
        }
        
        #dropZone {
            width: 100%;
            height: 100%;
        }
        
        #previewImage {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* New styles for image containers */
        .image-container {
            height: 400px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Ensure the flex layout properly handles tall content */
        .flex-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .flex-grow-container {
            flex: 1;
            min-height: 0;
            overflow: auto;
        }

        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .loading-content {
            background-color: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
        }

        .loading-spinner {
            display: inline-block;
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        .loading-spinner-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid #3B82F6;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <!-- Header Bar -->
    <div class="bg-white shadow-md w-full flex-shrink-0">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">AutoShop Image Editor</h1>
                <a href="?logout" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-1 min-h-0">
        <!-- Main Content Area (75%) -->
        <div id="mainContent" class="w-3/4 p-6 flex flex-col">
            <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col h-full">
                <div class="mb-4 flex-shrink-0">
                    <textarea id="prompt" class="w-full h-28 p-4 border rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg" 
                        placeholder="Enter your prompt here..."></textarea>
                </div>
                
                <div class="flex-grow-container mb-4 flex flex-wrap gap-4">
                    <!-- Primary Image Container -->
                    <div id="primaryImageContainer" class="flex-1 min-w-[45%] border-2 border-dashed border-gray-300 rounded-lg p-4 relative image-container">
                        <div id="primaryDropZone" class="h-full flex items-center justify-center">
                            <div id="primaryUploadPrompt" class="absolute inset-0 flex flex-col items-center justify-center">
                                <p class="mb-4 text-gray-600 text-lg">Drag and drop primary image here or</p>
                                <button id="primaryUploadBtn" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors text-lg">
                                    Choose File
                                </button>
                                <input type="file" id="primaryFileInput" class="hidden" accept="image/*">
                            </div>
                            <img id="primaryPreviewImage" class="preview-image hidden" src="" alt="Primary Image">
                        </div>
                        <button id="primaryRemoveImageBtn" class="absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full hidden hover:bg-red-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Secondary Image Container (initially hidden) -->
                    <div id="secondaryImageContainer" class="hidden flex-1 min-w-[45%] border-2 border-dashed border-gray-300 rounded-lg p-4 relative image-container">
                        <div id="secondaryDropZone" class="h-full flex items-center justify-center">
                            <div id="secondaryUploadPrompt" class="absolute inset-0 flex flex-col items-center justify-center">
                                <p class="mb-4 text-gray-600 text-lg">Drag and drop secondary image here or</p>
                                <button id="secondaryUploadBtn" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors text-lg">
                                    Choose File
                                </button>
                                <input type="file" id="secondaryFileInput" class="hidden" accept="image/*">
                            </div>
                            <img id="secondaryPreviewImage" class="preview-image hidden" src="" alt="Secondary Image">
                        </div>
                        <button id="secondaryRemoveImageBtn" class="absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full hidden hover:bg-red-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button id="generateBtn" class="w-full bg-green-500 text-white py-4 px-6 rounded-lg hover:bg-green-600 transition-colors text-lg font-semibold flex-shrink-0">
                    Generate Image
                </button>
            </div>
        </div>

        <!-- History Panel (25%) -->
        <div id="historyPanel" class="w-1/4 bg-gray-50 p-6 border-l border-gray-200 flex flex-col">
            <div class="bg-white rounded-xl shadow-lg p-4 flex flex-col h-full">
                <div class="flex justify-between items-center mb-4 pb-2 border-b flex-shrink-0">
                    <h2 class="text-xl font-bold text-gray-800">History</h2>
                    <button id="clearHistoryBtn" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors text-sm">
                        Clear History
                    </button>
                </div>
                <div id="historyContainer" class="flex-1 space-y-4 overflow-y-auto pr-2">
                    <!-- History items will be added here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-content">
            <div class="loading-spinner">
                <div class="loading-spinner-circle"></div>
            </div>
            <p class="text-lg font-medium text-gray-700">Generating image...</p>
        </div>
    </div>

    <script>
        // Initialize the first system prompt
        const systemPrompt = {
            role: "user",
            parts: [{
                text: "You are helping a user to create or edit an image. You will receive each time instruction for your next step to edit or create. Only answer with an image. Try to be the closest to the user needs. If editing an image, try to keep the style of the image as close as possible. DO NOT ANY TEXT, JUST THE IMAGE."
            }]
        };

        // Initialize history with system prompt
        let history = [systemPrompt];
        let activeHistoryIndex = -1; // Track which history item's MODEL response is active (e.g., index 2 for initial state)
        let primaryImage = null; // Stores base64 data URL
        let secondaryImage = null; // Stores base64 data URL
        let isPrimaryGenerated = false; // Track if primary image is generated vs initial upload

        // Primary Image Elements
        const primaryImageContainer = document.getElementById('primaryImageContainer');
        const primaryDropZone = document.getElementById('primaryDropZone');
        const primaryFileInput = document.getElementById('primaryFileInput');
        const primaryUploadBtn = document.getElementById('primaryUploadBtn');
        const primaryPreviewImage = document.getElementById('primaryPreviewImage');
        const primaryUploadPrompt = document.getElementById('primaryUploadPrompt');
        const primaryRemoveImageBtn = document.getElementById('primaryRemoveImageBtn');

        // Secondary Image Elements
        const secondaryImageContainer = document.getElementById('secondaryImageContainer');
        const secondaryDropZone = document.getElementById('secondaryDropZone');
        const secondaryFileInput = document.getElementById('secondaryFileInput');
        const secondaryUploadBtn = document.getElementById('secondaryUploadBtn');
        const secondaryPreviewImage = document.getElementById('secondaryPreviewImage');
        const secondaryUploadPrompt = document.getElementById('secondaryUploadPrompt');
        const secondaryRemoveImageBtn = document.getElementById('secondaryRemoveImageBtn');

        // Set up event listeners for primary image
        primaryUploadBtn.addEventListener('click', () => primaryFileInput.click());
        primaryFileInput.addEventListener('change', (e) => handleFileSelect(e, 'primary'));
        setupDragAndDrop(primaryImageContainer, 'primary');

        // Set up event listeners for secondary image
        secondaryUploadBtn.addEventListener('click', () => secondaryFileInput.click());
        secondaryFileInput.addEventListener('change', (e) => handleFileSelect(e, 'secondary'));
        setupDragAndDrop(secondaryImageContainer, 'secondary');

        // Event listener for primary remove button
        primaryRemoveImageBtn.addEventListener('click', () => {
            // Check if removal is allowed (based on current state)
            if (!isPrimaryGenerated) {
                removePrimaryImage(); // Clears global var and UI only
                updateInitialHistoryEntry(); // NOW update history[2] based on new global state
                updateHistoryDisplay(); // Refresh history panel
            } else {
                alert('Cannot remove a generated image. Clear history or select the "Initial Upload" item to modify.');
            }
        });

        // Event listener for secondary remove button
        secondaryRemoveImageBtn.addEventListener('click', () => {
            // Check if removal is allowed (based on current state)
            if (!isPrimaryGenerated) {
                removeSecondaryImage(); // Clears global var and UI only
                updateInitialHistoryEntry(); // NOW update history[2] based on new global state
                updateHistoryDisplay(); // Refresh history panel
            }
            // No alert needed if generated, button should be hidden anyway.
        });

        // Helper to get mime type from data URL
        function getImageMimeType(dataUrl) {
            if (!dataUrl) return null;
            const match = dataUrl.match(/^data:(image\/[a-z]+);base64,/);
            return match ? match[1] : 'image/jpeg'; // Default fallback
        }

        function setupDragAndDrop(container, type) {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Allow drag on primary always.
                // Allow drag on secondary ONLY if we are in the initial state (!isPrimaryGenerated) and primary exists
                if (type === 'primary' || (type === 'secondary' && !isPrimaryGenerated && primaryImage && history.length >= 3)) {
                     container.classList.add('border-blue-500');
                }
            });

            container.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.classList.remove('border-blue-500');
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.classList.remove('border-blue-500');

                // Prevent drop on secondary if not in the initial state or primary doesn't exist yet
                 if (type === 'secondary' && (isPrimaryGenerated || !primaryImage || history.length < 3)) {
                    alert('Secondary image can only be added or changed in the initial upload state, after a primary image is present.');
                    return;
                }

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0], type);
                }
            });
        }

        function handleFileSelect(e, type) {
            const file = e.target.files[0];
            if (file) {
                handleFile(file, type);
                // Reset the file input value so the same file can be selected again
                e.target.value = '';
            }
        }

        function handleFile(file, type) {
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const imageData = e.target.result; // Base64 data URL

                if (type === 'primary') {
                    const wasFirstUpload = history.length === 1; // Check BEFORE potentially modifying history
                    primaryImage = imageData;
                    primaryPreviewImage.src = primaryImage;
                    primaryPreviewImage.classList.remove('hidden');
                    primaryUploadPrompt.classList.add('hidden');
                    primaryRemoveImageBtn.classList.remove('hidden');
                    isPrimaryGenerated = false; // Reset flag on new primary upload

                    if (wasFirstUpload) {
                        // Create initial history entry
                        createInitialHistoryEntry();
                        secondaryImageContainer.classList.remove('hidden'); // Show secondary on first primary upload
                        updateHistoryDisplay(); // Update display immediately
                    } else {
                         // If we re-upload primary while viewing initial state, update it
                         if (activeHistoryIndex === 2 || activeHistoryIndex === -1) {
                            updateInitialHistoryEntry();
                            // If secondary existed, it's now orphaned, clear it
                            if (secondaryImage) {
                                removeSecondaryImage();
                            }
                            secondaryImageContainer.classList.remove('hidden'); // Ensure secondary is visible
                            updateHistoryDisplay();
                         } else {
                             // Uploading primary while viewing a *generated* image implies starting over
                             // Clear history and treat as first upload
                             clearHistory();
                             primaryImage = imageData; // Re-assign after clear
                             primaryPreviewImage.src = primaryImage;
                             primaryPreviewImage.classList.remove('hidden');
                             primaryUploadPrompt.classList.add('hidden');
                             primaryRemoveImageBtn.classList.remove('hidden');
                             createInitialHistoryEntry();
                             secondaryImageContainer.classList.remove('hidden');
                             updateHistoryDisplay();
                         }
                    }
                } else { // type === 'secondary'
                    // Allow only if primary exists and we're in initial state
                    if (isPrimaryGenerated || !primaryImage || history.length < 3) {
                        alert('Secondary image can only be added or changed in the initial upload state, after a primary image is present.');
                        return;
                    }
                    secondaryImage = imageData;
                    secondaryPreviewImage.src = secondaryImage;
                    secondaryPreviewImage.classList.remove('hidden');
                    secondaryUploadPrompt.classList.add('hidden');
                    secondaryRemoveImageBtn.classList.remove('hidden');

                    // Update the initial history entry to include the secondary image
                    updateInitialHistoryEntry();
                    updateHistoryDisplay(); // Update display immediately
                }
            };
            reader.readAsDataURL(file);
        }

        function createInitialHistoryEntry() {
            if (history.length !== 1 || !primaryImage) return; // Should only happen once with a primary image

            const initialUserPrompt = { role: "user", parts: [{ text: "Initial Upload" }] };
            const initialModelResponse = {
                role: "model",
                parts: [{
                    inlineData: {
                        mimeType: getImageMimeType(primaryImage),
                        data: primaryImage.split(',')[1]
                    }
                }]
                // Secondary image will be added by updateInitialHistoryEntry if needed
            };
            history.push(initialUserPrompt, initialModelResponse);
            activeHistoryIndex = 2; // Point to the initial model state
        }

        function updateInitialHistoryEntry() {
            // Ensure initial state exists and we have at least a primary image
            if (history.length < 3 || history[1].parts[0].text !== "Initial Upload") return;

            const parts = [];
            if (primaryImage) {
                parts.push({
                    inlineData: {
                        mimeType: getImageMimeType(primaryImage),
                        data: primaryImage.split(',')[1]
                    }
                });
            }
            if (secondaryImage) {
                 parts.push({
                    inlineData: {
                        mimeType: getImageMimeType(secondaryImage),
                        data: secondaryImage.split(',')[1]
                    }
                });
            }

            // Only update if parts are not empty (avoid creating empty initial state)
            if (parts.length > 0) {
                history[2].parts = parts;
            } else {
                 // If both images are removed, reset history? Or just leave empty parts?
                 // Let's reset - user should use clear history if they want to start over completely.
                 // For now, just update parts to empty, remove buttons handle UI.
                 history[2].parts = [];
            }
        }

        // Generate Image
        document.getElementById('generateBtn').addEventListener('click', async () => {
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }
            // Require at least a primary image (either uploaded or generated)
            if (!primaryImage) {
                alert('Please upload or generate a primary image first.');
                return;
            }

            document.getElementById('loadingOverlay').classList.remove('hidden');

            let historyForAPI = [];
            let isGeneratingFromInitial = false;

            // 1. Add system prompt
            historyForAPI.push(systemPrompt);

            // 2. Determine the base context (previous prompts/images) and image(s) for the *current* user prompt
            if (activeHistoryIndex === 2) { // Explicitly generating from the initial uploaded state
                // No prior history needed for API call
                // Trim actual history if we branched (although index 2 is the base, nothing to trim yet)
                history = history.slice(0, 3); // Keep system, initial user, initial model
                isGeneratingFromInitial = true;
            } else if (activeHistoryIndex > 2) { // Generating from a previous *generated* state
                // Include history up to the selected point for the API call
                // Slice from index 3 (skip initial state placeholders) up to and including the selected model response (activeHistoryIndex)
                historyForAPI = historyForAPI.concat(history.slice(3, activeHistoryIndex + 1));

                // Trim actual history if we branched from an older state
                history = history.slice(0, activeHistoryIndex + 1);
                isGeneratingFromInitial = false;
            } else { // activeHistoryIndex is -1 (generating from the latest state)
                // Include all generated history for the API call (skip initial placeholders)
                if (history.length > 3) {
                     historyForAPI = historyForAPI.concat(history.slice(3));
                     isGeneratingFromInitial = false;
                } else {
                    // History length is 3, meaning only system + initial pair exists
                    isGeneratingFromInitial = true;
                }
            }

            // 3. Create the new user prompt part for the API call
            const currentUserPrompt = {
                role: "user",
                parts: [{ text: prompt }]
            };

            // ONLY add image data to the user prompt if generating from the initial state
            if (isGeneratingFromInitial) {
                if (primaryImage) {
                    currentUserPrompt.parts.push({
                        inlineData: {
                            mimeType: getImageMimeType(primaryImage),
                            data: primaryImage.split(',')[1]
                        }
                    });
                }
                if (secondaryImage) {
                    currentUserPrompt.parts.push({
                        inlineData: {
                            mimeType: getImageMimeType(secondaryImage),
                            data: secondaryImage.split(',')[1]
                        }
                    });
                }
            };

            // 4. Add the new user prompt to the API history
            historyForAPI.push(currentUserPrompt);

            // 5. Add the new user prompt to the actual history (for UI display)
            // We will add the corresponding model response later upon success
            history.push(currentUserPrompt);

            try {
                const response = await fetch('./api/generate_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        contents: historyForAPI // Send the carefully constructed history
                    })
                });

                // Check for non-2xx status codes first
                if (!response.ok) {
                    let errorData;
                    try {
                        errorData = await response.json();
                    } catch (e) {
                        errorData = { error: 'Failed to parse error response', details: await response.text() };
                    }

                    // Handle specific error codes if needed (like 422 for safety)
                    if (response.status === 422 && errorData.details) {
                         throw new Error(`Image generation failed: ${errorData.details}`);
                    } else {
                        throw new Error(`HTTP error ${response.status}: ${errorData.error || 'Unknown API error'}`);
                    }
                }

                const data = await response.json(); // Assuming 2xx means valid JSON response structure

                // It's still good practice to check the structure even after response.ok
                if (data.candidates && data.candidates[0]?.finishReason === 'IMAGE_SAFETY') {
                    throw new Error('The image could not be generated due to safety concerns. Please try a different prompt or image.');
                }
                
                // Find the first image part in the response
                let generatedImagePart = null;
                if (data.candidates?.[0]?.content?.parts && Array.isArray(data.candidates[0].content.parts)) {
                    console.log('Searching through', data.candidates[0].content.parts.length, 'parts for image data');
                    for (let i = 0; i < data.candidates[0].content.parts.length; i++) {
                        const part = data.candidates[0].content.parts[i];
                        console.log(`Part ${i}:`, part);
                        if (part.inlineData && part.inlineData.mimeType && part.inlineData.mimeType.startsWith('image/')) {
                            console.log(`Found image at part ${i}:`, part.inlineData.mimeType);
                            generatedImagePart = part.inlineData;
                            break;
                        }
                    }
                }
                
                if (!generatedImagePart) {
                    console.error("No image found in API response:", data);
                    throw new Error('No image was generated. Please try a different prompt.');
                }
                const generatedImageBase64 = generatedImagePart.data;
                const generatedMimeType = generatedImagePart.mimeType;

                // Update primary image display
                primaryImage = `data:${generatedMimeType};base64,${generatedImageBase64}`;
                primaryPreviewImage.src = primaryImage;
                primaryPreviewImage.classList.remove('hidden');
                primaryUploadPrompt.classList.add('hidden');
                primaryRemoveImageBtn.classList.add('hidden');    // Hide remove button after generation
                isPrimaryGenerated = true; // Mark as generated

                // Add the model response to the actual history
                const modelResponse = {
                    role: "model",
                    parts: [{
                        inlineData: {
                            mimeType: generatedMimeType,
                            data: generatedImageBase64
                        }
                    }]
                };
                history.push(modelResponse);

                activeHistoryIndex = -1; // Reset active index after new generation completes
                updateHistoryDisplay();

                // Hide secondary container, clear secondary image variable and UI
                secondaryImageContainer.classList.add('hidden');
                removeSecondaryImage(); // Clear the variable and UI (safe even if already cleared)

                // Clear the prompt
                document.getElementById('prompt').value = '';

            } catch (error) {
                console.error('Error during generation:', error);
                alert(`An error occurred: ${error.message}`);
                // Remove the user prompt we optimistically added if API call failed
                 if (history[history.length - 1]?.role === 'user') {
                     history.pop();
                 }
                 // Reset active index and update display to reflect failed state
                 activeHistoryIndex = history.length > 1 ? history.length - 1 : -1;
                 updateHistoryDisplay();

            } finally {
                document.getElementById('loadingOverlay').classList.add('hidden');
            }
        });

        function updateHistoryDisplay() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = ''; // Clear and rebuild entire history

            // Iterate backwards by pairs, skipping system prompt (index 0)
            // Start from the index of the last potential model item
            for (let i = history.length - 1; i >= 2; i -= 1) {
                const currentItem = history[i];
                const previousItem = history[i-1]; // User prompt or system prompt

                // We are looking for model items preceded by user items
                if (currentItem.role === 'model' && previousItem?.role === 'user') {
                    const modelIndex = i; // Index of the model item
                    const userIndex = i - 1; // Index of the user item

                    const historyItem = document.createElement('div');
                    // Check if this MODEL item is the active one OR (no item is active AND this is the latest MODEL item)
                    const isActive = modelIndex === activeHistoryIndex || (activeHistoryIndex === -1 && modelIndex === history.length - 1);

                    historyItem.className = `border rounded-lg p-4 mb-4 cursor-pointer transition-all ${
                        isActive ? 'bg-white shadow-md ring-2 ring-blue-500' : 'bg-white shadow-sm hover:shadow-md'
                    }`;
                    historyItem.dataset.index = modelIndex; // Store the index of the MODEL item

                    // Add prompt text (always the first part of the user message)
                    const promptDiv = document.createElement('div');
                    promptDiv.className = 'text-sm text-gray-600 mb-2 line-clamp-2';
                    // Get text from userItem (index i-1)
                    const promptText = previousItem.parts[0]?.text || 'Prompt unavailable';
                    promptDiv.textContent = promptText;
                    historyItem.appendChild(promptDiv);

                    // Add image (use the first image part of the model message for thumbnail)
                    if (currentItem.parts && currentItem.parts[0]?.inlineData) {
                        const img = document.createElement('img');
                        const imageData = currentItem.parts[0].inlineData.data;
                        const mimeType = currentItem.parts[0].inlineData.mimeType;
                        img.src = `data:${mimeType};base64,${imageData}`;
                        img.className = 'w-full h-auto rounded-md';
                        historyItem.appendChild(img);
                    } else if (modelIndex === 2) { // Specifically for the initial upload item
                         // If history[2] itself has no image data, show placeholder
                         const placeholder = document.createElement('div');
                         placeholder.className = 'text-sm text-gray-400 italic text-center py-4';
                         placeholder.textContent = 'Initial image(s) removed';
                         historyItem.appendChild(placeholder);
                    }


                    // Add click handler to select the MODEL item
                    historyItem.addEventListener('click', () => selectHistoryItem(modelIndex));

                    container.appendChild(historyItem);

                    // Decrement again since we processed a pair (model and user)
                    i--;
                }
            }

            // Scroll to top after updating
            container.scrollTop = 0;
        }


        function selectHistoryItem(index) {
            // index points to the 'model' part of the pair in the history array
            if (index < 2 || index >= history.length || history[index].role !== 'model') {
                console.warn("Invalid index passed to selectHistoryItem:", index);
                return;
            }
            activeHistoryIndex = index;

            const selectedModelItem = history[index];
            // Determine if the target state is a generated one *before* resetting UI
            const targetIsGenerated = index > 2;

            const primaryImageDataPart = selectedModelItem.parts[0]?.inlineData;
            // Secondary image only exists in the initial state (index 2)
            const secondaryImageDataPart = (index === 2) ? selectedModelItem.parts[1]?.inlineData : null;

            // --- Reset UI state first ---
            // Set the generation flag based on the *target* state *before* calling reset helpers
            isPrimaryGenerated = targetIsGenerated;

            // Clear primary
            primaryImage = null;
            primaryPreviewImage.src = '';
            primaryPreviewImage.classList.add('hidden');
            primaryUploadPrompt.classList.remove('hidden');
            primaryRemoveImageBtn.classList.add('hidden');
            // Clear secondary
            removeSecondaryImage(); // Now respects the isPrimaryGenerated flag set above
            secondaryImageContainer.classList.add('hidden'); // Default to hidden

            // --- Apply selected state ---
            if (!targetIsGenerated) { // Selecting the initial uploaded state (index === 2)
                // Restore primary image if it exists in history[2]
                if (primaryImageDataPart) {
                    primaryImage = `data:${primaryImageDataPart.mimeType};base64,${primaryImageDataPart.data}`;
                    primaryPreviewImage.src = primaryImage;
                    primaryPreviewImage.classList.remove('hidden');
                    primaryUploadPrompt.classList.add('hidden');
                    primaryRemoveImageBtn.classList.remove('hidden'); // Show remove button for initial upload
                } else {
                    primaryUploadPrompt.classList.remove('hidden'); // Ensure upload prompt shows if no primary image
                }

                // Restore secondary image if it existed in the initial state history item
                if (secondaryImageDataPart) {
                    secondaryImage = `data:${secondaryImageDataPart.mimeType};base64,${secondaryImageDataPart.data}`;
                    secondaryPreviewImage.src = secondaryImage;
                    secondaryPreviewImage.classList.remove('hidden');
                    secondaryUploadPrompt.classList.add('hidden');
                    secondaryRemoveImageBtn.classList.remove('hidden');
                } else {
                     secondaryUploadPrompt.classList.remove('hidden'); // Ensure upload prompt shows if no secondary image
                }
                // Show secondary container when viewing initial state (even if empty)
                secondaryImageContainer.classList.remove('hidden');

            } else { // Selecting a generated state (targetIsGenerated is true)
                 // Update the primary image preview with the generated image
                if (primaryImageDataPart) {
                    primaryImage = `data:${primaryImageDataPart.mimeType};base64,${primaryImageDataPart.data}`;
                    primaryPreviewImage.src = primaryImage;
                    primaryPreviewImage.classList.remove('hidden');
                    primaryUploadPrompt.classList.add('hidden');
                    primaryRemoveImageBtn.classList.add('hidden'); // Hide remove button for generated images
                } else {
                     // Should not happen for generated images, but handle defensively
                     primaryUploadPrompt.classList.remove('hidden');
                }
                // Hide secondary container for generated images
                secondaryImageContainer.classList.add('hidden');
                // Secondary image variable is already cleared above
            }

            // Update history display highlights
            updateHistoryDisplay();

            // --- Update prompt textarea ---
            const promptElement = document.getElementById('prompt');
            const nextUserPromptIndex = index + 1;
            if (nextUserPromptIndex < history.length && history[nextUserPromptIndex]?.role === 'user' && history[nextUserPromptIndex].parts[0]?.text) {
                promptElement.value = history[nextUserPromptIndex].parts[0].text;
            } else {
                // If it's the last item, or next item isn't a valid prompt, clear the textarea.
                promptElement.value = '';
            }
        }


        function removePrimaryImage() {
            // Only allow removal if viewing the initial upload state
            if (isPrimaryGenerated) {
                return; // Prevent state change if generated
            }

            // If we are in the initial upload state (!isPrimaryGenerated)
            primaryImage = null;
            primaryPreviewImage.src = '';
            primaryPreviewImage.classList.add('hidden');
            primaryUploadPrompt.classList.remove('hidden');
            primaryRemoveImageBtn.classList.add('hidden');

            // Update the initial history state (index 2) to reflect removal
            updateInitialHistoryEntry();
            updateHistoryDisplay(); // Reflect the change in history panel

            // If secondary image exists, it remains. Secondary container stays visible
            // as we are still conceptually in the 'initial state'.
            if (!isPrimaryGenerated && history.length >= 3) { // Ensure we are conceptually in initial state
                secondaryImageContainer.classList.remove('hidden');
            }
        }

        function removeSecondaryImage() {
            // Only allow removal if viewing the initial upload state
            if (isPrimaryGenerated) return;

            secondaryImage = null;
            secondaryPreviewImage.src = '';
            secondaryPreviewImage.classList.add('hidden');
            secondaryUploadPrompt.classList.remove('hidden');
            secondaryRemoveImageBtn.classList.add('hidden');
        }

        function clearHistory() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = '';
            history = [systemPrompt]; // Reset to only the system prompt
            activeHistoryIndex = -1;
            isPrimaryGenerated = false;

            // Clear primary image state and UI
            primaryImage = null;
            primaryPreviewImage.src = '';
            primaryPreviewImage.classList.add('hidden');
            primaryUploadPrompt.classList.remove('hidden');
            primaryRemoveImageBtn.classList.add('hidden');

            // Clear secondary image state and UI
            secondaryImage = null;
            secondaryPreviewImage.src = '';
            secondaryPreviewImage.classList.add('hidden');
            secondaryUploadPrompt.classList.remove('hidden');
            secondaryRemoveImageBtn.classList.add('hidden');

            // Hide secondary container
            secondaryImageContainer.classList.add('hidden');

            // Clear the prompt textarea
            document.getElementById('prompt').value = '';
        }

        // Initialize clear history button
        document.getElementById('clearHistoryBtn').addEventListener('click', clearHistory);
    </script>
</body>
</html> 