<?php include '../header.php'; ?>

<div class="card">
    <h2>Kontakt</h2>
    <p>Haben Sie Fragen? Kontaktieren Sie uns gerne!</p>
    
    <form method="POST" class="contact-form">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">E-Mail:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="subject">Betreff:</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="form-group">
            <label for="message">Nachricht:</label>
            <textarea id="message" name="message" rows="6" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Nachricht senden</button>
    </form>
</div>

<?php include '../footer.php'; ?>
