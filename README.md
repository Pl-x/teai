# 🍃 Tea Leaf Disease Detection System

An AI-powered web application capable of classifying tea leaf diseases from images. This system uses a Deep Learning model (TensorFlow/Keras) integrated with a PHP web interface to provide real-time diagnosis, confidence scores, visual analysis, and treatment recommendations.

## 🚀 Features

* **AI Diagnosis:** Detects 8 different conditions including Anthracnose, Algal Leaf, Bird Eye Spot, and more
* **Visual Analysis:** Generates bar charts showing probability distribution for each prediction
* **Dual Input:** Support for file upload and live camera capture (mobile-friendly)
* **Detailed Reporting:** Provides confidence scores and specific treatment solutions
* **User Dashboard:** Prediction history, dark/light mode toggle, and responsive sidebar
* **Robust Backend:** PHP-based processing with error logging and fail-safe mechanisms

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
* **Backend:** PHP (Session management, File handling, Database interactions)
* **Database:** PostgreSQL (via PDO)
* **AI Engine:** Python 3, TensorFlow (Keras), NumPy
* **Visualization:** Matplotlib

## 📂 Project Structure

```text
/teai
├── backend/
│   └── db.php              # Database connection configuration
├── model/
│   ├── tea-model.hdf5      # Trained Keras model file
│   ├── predict.py          # Core AI logic and visualization
│   └── predict_cli.py      # CLI Wrapper to interface between PHP and Python
├── uploads/                # Stores uploaded user images (auto-created)
├── log/                    # Stores error and execution logs (auto-created)
├── dashboard.php           # Main user interface
├── predict.php             # Form handler (runs python script & updates DB)
├── login.php               # User authentication
└── README.md
```

## ⚙️ Installation & Setup

### 1. Prerequisites

* Web Server (Apache/Nginx) with PHP 7.4 or higher
* Python 3.8 - 3.11 (Ensure it is added to system PATH)
* Database (PostgreSQL)

* The database i am using is Postgresql since it scales well and it's easy to setup in docker  so the php PDO is specific to Postgres.
* If your want to use another DB make sure to check your DB documentation and PHP's for integration.
* The tech stack is vanilla to minimize frameworks overkill and setup but it might shift to laravel and Flask in future.

### 2. Python Dependencies

Install the required Python libraries using uv:
We choose uv because it makes managemnt easy eliminating the need for multiple untracked environments that may become cumbersome as the codebase scales.
Check Out uv's official documentation at https://docs.astral.sh/uv.
Think of it as npm...package manager for python. Sync the pyproject.toml file to install the dependencies.

```bash
cd model
uv sync
```

### 3. PHP Dependencies

The project uses composer to sync and manage php dependencies:
Install composer if not present

```bash
composer install

sleep 5

cd backend
composer require vlucas/phpdotenv
```


### 3. Database Configuration

Import the database schema. Ensure your predictions table exists:

```sql
CREATE TABLE predictions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255),
    disease VARCHAR(100),
    confidence FLOAT,
    probabilities JSON,
    solution TEXT,
    visualization_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Update `backend/db.php` with your database credentials.

### 4. File Permissions (Linux/Mac)

Ensure the web server has permission to write to the uploads and log directories and execute the Python script:

```bash
chmod -R 775 uploads/
chmod -R 775 log/
chmod +x model/predict_cli.py
```

## 🖥️ Usage

1. Login to the application
2. Navigate to the Dashboard
3. Upload an image of a tea leaf OR use the Camera button to take a photo
4. Click Analyze Image
5. View the results, including the predicted disease, confidence percentage, treatment advice, and the generated probability graph

## 🔧 Troubleshooting

### 1. Image upload fails

* Check directory permissions for `uploads/`
* Check PHP `upload_max_filesize` in `php.ini`

### 2. Visualization graph not showing

* Ensure matplotlib is installed
* Check if the PHP script has write permissions to create the `_vis.png` file in the `uploads/` folder

### 3. The Model

* The model is an Image Classification CNN(Convolutionary Neural Network) from a third party developer.
* It takes input images of 180 * 180 px with 3 RGB color channels.
* The output is based on predefined classes(8) that follow a probability distribution across each.
* Output: Object Recognition
          Scene Classification
          Medical Imaging
          Plant Species

## 📄 License

MIT Public License - Copyright © 2025