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
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent"></div>
            <p class="mt-4 text-center text-lg font-medium">Generating image...</p>
        </div>
    </div>

    <script>
        // Initialize the first system prompt
        const systemPrompt = {
            role: "user",
            parts: [{
                text: "You are helping a user to create or edit an image. You will receive each time instruction for your next step to edit or create. Only answer with an image. Try to be the closest to the user needs. If editing an image, try to keep the style of the image as close as possible"
            }]
        };

        // Initialize history with system prompt
        let history = [systemPrompt];
        let activeHistoryIndex = -1; // Track which history item is active
        let primaryImage = null;
        let secondaryImage = null;
        let isPrimaryGenerated = false; // Track if primary image is generated

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
        primaryRemoveImageBtn.addEventListener('click', removePrimaryImage);
        primaryFileInput.addEventListener('change', (e) => handleFileSelect(e, 'primary'));
        setupDragAndDrop(primaryImageContainer, 'primary');

        // Set up event listeners for secondary image
        secondaryUploadBtn.addEventListener('click', () => secondaryFileInput.click());
        secondaryRemoveImageBtn.addEventListener('click', removeSecondaryImage);
        secondaryFileInput.addEventListener('change', (e) => handleFileSelect(e, 'secondary'));
        setupDragAndDrop(secondaryImageContainer, 'secondary');

        function setupDragAndDrop(container, type) {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.classList.add('border-blue-500');
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
            }
        }

        function handleFile(file, type) {
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const imageData = e.target.result;
                
                if (type === 'primary') {
                    primaryImage = imageData;
                    primaryPreviewImage.src = primaryImage;
                    primaryPreviewImage.classList.remove('hidden');
                    primaryUploadPrompt.classList.add('hidden');
                    primaryRemoveImageBtn.classList.remove('hidden');
                    isPrimaryGenerated = false;
                    
                    // Show secondary image container when primary image is added
                    secondaryImageContainer.classList.remove('hidden');
                } else {
                    secondaryImage = imageData;
                    secondaryPreviewImage.src = secondaryImage;
                    secondaryPreviewImage.classList.remove('hidden');
                    secondaryUploadPrompt.classList.add('hidden');
                    secondaryRemoveImageBtn.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(file);
        }

        // Generate Image
        document.getElementById('generateBtn').addEventListener('click', async () => {
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }

            if (!primaryImage) {
                alert('Please upload at least one image');
                return;
            }

            document.getElementById('loadingOverlay').classList.remove('hidden');

            // If we have an active history item, trim history to that point before adding new content
            if (activeHistoryIndex !== -1) {
                history = history.slice(0, activeHistoryIndex + 1);
            }

            // Add the current prompt and image(s) to the request
            const currentPrompt = {
                role: "user",
                parts: [{ text: prompt }]
            };

            // Only add primary image if it's not from history (not generated)
            if (!isPrimaryGenerated) {
                currentPrompt.parts.push({
                    inlineData: {
                        mimeType: "image/jpeg",
                        data: primaryImage.split(',')[1]
                    }
                });
            }

            // Add secondary image if it exists
            if (secondaryImage) {
                currentPrompt.parts.push({
                    inlineData: {
                        mimeType: "image/jpeg",
                        data: secondaryImage.split(',')[1]
                    }
                });
            }

            history.push(currentPrompt);

            try {
                const response = await fetch('./api/generate_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        contents: history
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.candidates && data.candidates[0].finishReason === 'IMAGE_SAFETY') {
                    throw new Error('The image could not be generated due to safety concerns. Please try a different prompt or image.');
                }
                
                if (!data.candidates || !data.candidates[0].content || !data.candidates[0].content.parts) {
                    throw new Error('Unexpected API response format. Please try again.');
                }

                const generatedImage = data.candidates[0].content.parts[0].inlineData.data;
                
                // Update the preview with the generated image
                primaryImage = `data:image/png;base64,${generatedImage}`;
                primaryPreviewImage.src = primaryImage;
                primaryPreviewImage.classList.remove('hidden');
                primaryUploadPrompt.classList.add('hidden');
                primaryRemoveImageBtn.classList.remove('hidden');
                isPrimaryGenerated = true;
                
                // Clear secondary image after generation
                removeSecondaryImage();

                // Add the response to history
                history.push({
                    role: "model",
                    parts: [{
                        inlineData: {
                            mimeType: "image/png",
                            data: generatedImage
                        }
                    }]
                });

                activeHistoryIndex = -1; // Reset active index for new generation
                updateHistoryDisplay();

                // Clear the prompt
                document.getElementById('prompt').value = '';
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating the image');
            } finally {
                document.getElementById('loadingOverlay').classList.add('hidden');
            }
        });

        function updateHistoryDisplay() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = ''; // Clear and rebuild entire history

            // Create history items in reverse order (newest first)
            for (let i = history.length - 1; i > 0; i -= 2) { // Start from last item, skip system prompt
                if (history[i].role === 'model' && history[i-1].role === 'user') {
                    const historyItem = document.createElement('div');
                    const isActive = i === activeHistoryIndex || (activeHistoryIndex === -1 && i === history.length - 1);
                    
                    historyItem.className = `border rounded-lg p-4 mb-4 cursor-pointer transition-all ${
                        isActive ? 'bg-white shadow-md ring-2 ring-blue-500' : 'bg-white shadow-sm hover:shadow-md'
                    }`;
                    historyItem.dataset.index = i;

                    // Add prompt text
                    const promptDiv = document.createElement('div');
                    promptDiv.className = 'text-sm text-gray-600 mb-2 line-clamp-2';
                    // Get the prompt text, handling both regular prompts and initial image uploads
                    const promptText = history[i-1].parts[0].text;
                    promptDiv.textContent = promptText;
                    historyItem.appendChild(promptDiv);

                    // Add image
                    const img = document.createElement('img');
                    // For model responses, the image is in parts[0]
                    // For user uploads without generation, we need to get the image from parts[1]
                    const imageData = history[i].parts[0].inlineData.data;
                    img.src = `data:image/png;base64,${imageData}`;
                    img.className = 'w-full h-auto rounded-md';
                    historyItem.appendChild(img);

                    // Add click handler
                    historyItem.addEventListener('click', () => selectHistoryItem(i));

                    container.appendChild(historyItem);
                }
            }

            // Scroll to top after adding new item
            container.scrollTop = 0;
        }

        function selectHistoryItem(index) {
            activeHistoryIndex = index;
            
            // Update the current image preview
            const selectedImage = history[index].parts[0].inlineData.data;
            primaryImage = `data:image/png;base64,${selectedImage}`;
            primaryPreviewImage.src = primaryImage;
            primaryPreviewImage.classList.remove('hidden');
            primaryUploadPrompt.classList.add('hidden');
            primaryRemoveImageBtn.classList.remove('hidden');
            isPrimaryGenerated = true;
            
            // Show secondary container but clear any secondary image
            secondaryImageContainer.classList.remove('hidden');
            removeSecondaryImage();
            
            // Update history display to highlight selected item
            updateHistoryDisplay();
        }

        function removePrimaryImage() {
            // Only allow removal if it's not a generated image
            if (isPrimaryGenerated) {
                alert('Cannot remove a generated image. Please clear history to start over.');
                return;
            }

            primaryImage = null;
            primaryPreviewImage.src = '';
            primaryPreviewImage.classList.add('hidden');
            primaryUploadPrompt.classList.remove('hidden');
            primaryRemoveImageBtn.classList.add('hidden');
            isPrimaryGenerated = false;
            
            // If secondary image exists, make it the primary
            if (secondaryImage) {
                primaryImage = secondaryImage;
                primaryPreviewImage.src = secondaryImage;
                primaryPreviewImage.classList.remove('hidden');
                primaryUploadPrompt.classList.add('hidden');
                primaryRemoveImageBtn.classList.remove('hidden');
                
                // Clear secondary
                removeSecondaryImage();
            } else {
                // Hide secondary container if no images
                secondaryImageContainer.classList.add('hidden');
            }
        }

        function removeSecondaryImage() {
            secondaryImage = null;
            secondaryPreviewImage.src = '';
            secondaryPreviewImage.classList.add('hidden');
            secondaryUploadPrompt.classList.remove('hidden');
            secondaryRemoveImageBtn.classList.add('hidden');
        }

        function clearHistory() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = '';
            history = [systemPrompt]; // Keep only the system prompt
            activeHistoryIndex = -1;
            isPrimaryGenerated = false;
            
            // Clear primary image
            primaryImage = null;
            primaryPreviewImage.src = '';
            primaryPreviewImage.classList.add('hidden');
            primaryUploadPrompt.classList.remove('hidden');
            primaryRemoveImageBtn.classList.add('hidden');
            
            // Clear and hide secondary image
            secondaryImage = null;
            secondaryPreviewImage.src = '';
            secondaryPreviewImage.classList.add('hidden');
            secondaryUploadPrompt.classList.remove('hidden');
            secondaryRemoveImageBtn.classList.add('hidden');
            secondaryImageContainer.classList.add('hidden');
        }

        // Initialize clear history button
        document.getElementById('clearHistoryBtn').addEventListener('click', clearHistory);
    </script>
</body>
</html> 