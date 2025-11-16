<?php include '../header.php'; ?>

<div class="card">
    <h2>Contact</h2>
    <p>Have questions? Feel free to contact us!</p>
    
    <form method="POST" class="contact-form">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="6" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Send message</button>
    </form>
</div>

<?php include '../footer.php'; ?>
