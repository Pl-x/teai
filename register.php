<?php
session_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Create Account</title>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        height: 100vh;
        background: url('images/green-tea-bud.jpg') no-repeat center center/cover;
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(3px);
    }

    .container {
        position: relative;
        z-index: 2;
        width: 400px;
        margin: 50px auto;
        padding: 30px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 40px rgba(0, 0, 0, 0.3);
        color: white;
        max-height: 90vh;
        overflow-y: auto;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        font-weight: 600;
    }

    label {
        font-size: 14px;
        font-weight: 500;
    }

    input, select {
        width: 100%;
        padding: 12px;
        margin: 8px 0 15px 0;
        border: none;
        border-radius: 8px;
        outline: none;
        background: rgba(255,255,255,0.85);
        box-sizing: border-box;
    }

    .password-container {
        position: relative;
        width: 100%;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 16px;
        color: #555;
        user-select: none;
    }

    button {
        width: 100%;
        padding: 12px;
        border: none;
        background: #28a745;
        color: white;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
    }

    button:hover {
        background: #1e7e34;
    }

    .terms {
        margin-top: 10px;
        display: flex;
        align-items: center;
    }

    .terms input {
        width: auto;
        margin-right: 8px;
    }

    @media (max-width: 480px) {
        .container {
            width: 95%;
            margin-top: 30px;
        }
    }
</style>
</head>

<body>
<div class="overlay"></div>

<div class="container">
    <h2>Create Your Account</h2>

    <form method="post" action="register_handler.php">
        <?php
            if (empty($_SESSION['token'])) {
                $_SESSION['token'] = bin2hex(random_bytes(32));
            }
            $selected_country = isset($_POST['country']) ? $_POST['country'] : '';
            $selected_language = isset($_POST['language']) ? $_POST['language'] : '';
        ?>
        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

        <label>Name</label>
        <input name="name" required>

        <label>Email</label>
        <input name="email" type="email" required>

        <label>Password</label>
        <div class="password-container">
            <input id="password" name="password" type="password" required style="padding-right: 35px;">
            <span class="toggle-password" onclick="togglePassword('password')">&#128065;</span>
        </div>

        <label>Confirm Password</label>
        <div class="password-container">
            <input id="confirm_password" name="confirm_password" type="password" required style="padding-right: 35px;">
            <span class="toggle-password" onclick="togglePassword('confirm_password')">&#128065;</span>
        </div>

        <label>Country</label>
        <input list="countries" name="country" value="<?php echo $selected_country; ?>" required placeholder="Type or select country">
        <datalist id="countries">
            <?php
            $countries = ["Afghanistan","Albania","Algeria","Andorra","Angola","Antigua and Barbuda","Argentina","Armenia","Australia","Austria","Azerbaijan",
            "Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Brazil",
            "Brunei","Bulgaria","Burkina Faso","Burundi","Cabo Verde","Cambodia","Cameroon","Canada","Central African Republic","Chad","Chile","China",
            "Colombia","Comoros","Congo (Congo-Brazzaville)","Costa Rica","Croatia","Cuba","Cyprus","Czechia","Democratic Republic of the Congo",
            "Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Eswatini","Ethiopia",
            "Fiji","Finland","France","Gabon","Gambia","Georgia","Germany","Ghana","Greece","Grenada","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti",
            "Holy See","Honduras","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan",
            "Kenya","Kiribati","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg",
            "Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco",
            "Mongolia","Montenegro","Morocco","Mozambique","Myanmar","Namibia","Nauru","Nepal","Netherlands","New Zealand","Nicaragua","Niger","Nigeria",
            "North Korea","North Macedonia","Norway","Oman","Pakistan","Palau","Palestine State","Panama","Papua New Guinea","Paraguay","Peru","Philippines",
            "Poland","Portugal","Qatar","Romania","Russia","Rwanda","Saint Kitts and Nevis","Saint Lucia","Saint Vincent and the Grenadines","Samoa","San Marino",
            "Sao Tome and Principe","Saudi Arabia","Senegal","Serbia","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia",
            "South Africa","South Korea","South Sudan","Spain","Sri Lanka","Sudan","Suriname","Sweden","Switzerland","Syria","Tajikistan","Tanzania","Thailand",
            "Timor-Leste","Togo","Tonga","Trinidad and Tobago","Tunisia","Turkey","Turkmenistan","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom",
            "United States of America","Uruguay","Uzbekistan","Vanuatu","Venezuela","Vietnam","Yemen","Zambia","Zimbabwe"];
            foreach($countries as $country){
                echo "<option value=\"$country\">";
            }
            ?>
        </datalist>

        <label>Language</label>
        <input list="languages" name="language" value="<?php echo $selected_language; ?>" required placeholder="Type or select language">
        <datalist id="languages">
            <?php
            $languages = ["Afrikaans","Albanian","Amharic","Arabic","Armenian","Azerbaijani","Basque","Belarusian","Bengali","Bosnian","Bulgarian","Catalan",
            "Cebuano","Chichewa","Chinese","Corsican","Croatian","Czech","Danish","Dutch","English","Esperanto","Estonian","Filipino","Finnish","French",
            "Galician","Georgian","German","Greek","Gujarati","Haitian Creole","Hausa","Hawaiian","Hebrew","Hindi","Hmong","Hungarian","Icelandic","Igbo",
            "Indonesian","Irish","Italian","Japanese","Javanese","Kannada","Kazakh","Khmer","Kinyarwanda","Korean","Kurdish","Kyrgyz","Lao","Latin",
            "Latvian","Lithuanian","Luxembourgish","Macedonian","Malagasy","Malay","Malayalam","Maltese","Maori","Marathi","Mongolian","Myanmar (Burmese)",
            "Nepali","Norwegian","Odia (Oriya)","Pashto","Persian","Polish","Portuguese","Punjabi","Romanian","Russian","Samoan","Scots Gaelic","Serbian",
            "Sesotho","Shona","Sindhi","Sinhala","Slovak","Slovenian","Somali","Spanish","Sundanese","Swahili","Swedish","Tajik","Tamil","Tatar","Telugu",
            "Thai","Turkish","Turkmen","Ukrainian","Urdu","Uyghur","Uzbek","Vietnamese","Welsh","Xhosa","Yiddish","Yoruba","Zulu"];
            foreach($languages as $lang){
                echo "<option value=\"$lang\">";
            }
            ?>
        </datalist>

        <div class="terms">
            <input type="checkbox" required>
            <label>I agree to the Terms & Conditions</label>
        </div>

        <button type="submit">Register</button>
    </form>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}
</script>

</body>
</html>


