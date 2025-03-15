# AutoShop

AutoShop is a web application that allows users to create and edit images using Google's Gemini 2.0 Flash Exp API. The application provides an intuitive interface for entering prompts and optionally uploading images for editing.

## Features

- User authentication
- Text-to-image generation
- Image editing with prompts
- Drag-and-drop image upload
- History of generated images
- Real-time preview

## Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/autoshop.git
cd autoshop
```

2. Create a configuration file:
```bash
cp config-sample.php config.php
```

3. Edit `config.php` and set your credentials:
- Set your desired username and password
- Add your Gemini API key (obtain from Google AI Studio)
- Adjust other configuration settings if needed

4. Set up a PHP server:
```bash
php -S localhost:8000
```

5. Visit `http://localhost:8000` in your browser

## Requirements

- PHP 7.4 or higher
- Modern web browser with JavaScript enabled
- Google Gemini API key
- Internet connection for API calls and CDN resources

## Usage

1. Log in using the credentials set in `config.php`
2. Enter a prompt describing the image you want to generate
3. Optionally upload an image to edit
4. Click "Generate Image" to create or edit the image
5. View the history of generated images in the right panel

## Security Notes

- Never commit your `config.php` file
- Keep your API key secure
- Use HTTPS in production
- Regularly update dependencies

## License

This project is licensed under the MIT License - see the LICENSE file for details.
