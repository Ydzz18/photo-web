# LensCraft - User Guide

Welcome to **LensCraft**, a professional photography showcase platform where you can upload your photos, connect with fellow photographers, and share your passion for photography with the world.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Account Management](#account-management)
3. [Photo Management](#photo-management)
4. [Social Features](#social-features)
5. [User Profile](#user-profile)
6. [Settings](#settings)
7. [Search & Discovery](#search--discovery)
8. [Contact & Support](#contact--support)
9. [Frequently Asked Questions](#frequently-asked-questions)
10. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Creating a New Account

Follow these steps to join LensCraft:

1. **Visit the Registration Page**
   - Click "Start Sharing" on the homepage, or go to `/auth/register.php`

2. **Step 1: Personal Information**
   - Enter your **First Name** and **Last Name**
   - Enter your **Phone** number (optional)
   - Select your **Birthday**
   - Enter your **Address** (optional)
   - Click "Next Step"

3. **Step 2: Account Credentials**
   - Create a **Username** (must be unique)
   - Enter your **Email** (must be unique and valid)
   - Create a strong **Password**
   - Confirm your **Password**
   - Read and accept the **Terms & Conditions**
   - Click "Register"

4. **Verify Your Email**
   - Check your email inbox for a verification message from LensCraft
   - Click the verification link in the email
   - Your email will be marked as verified
   - You can now login to your account

**Note:** Email verification is required before you can fully use your account. If you don't receive the verification email, check your spam folder.

### Logging In

1. Go to `/auth/login.php`
2. Enter your **Email or Username**
3. Enter your **Password**
4. If you have **Two-Factor Authentication (2FA)** enabled, you'll be prompted to enter a 6-digit code sent to your email
5. Click "Login"

### Password Recovery

If you forget your password:

1. Go to `/auth/forgot-password.php`
2. Enter your **Email Address**
3. Click "Reset Password"
4. Check your email for a password reset link
5. Click the link and enter your new password
6. Use your new password to login

---

## Account Management

### Email Verification

Email verification ensures that your account is secure and that we can contact you when needed.

**First-Time Verification:**
- A verification email is sent automatically after registration
- Click the link in the email to verify your address
- The link expires in **24 hours**
- If the link expires, register again to receive a new verification email

**What if you don't receive the email?**
- Check your spam/junk folder
- Ensure you entered the correct email address
- Check that your email provider isn't blocking automated emails

### Two-Factor Authentication (2FA)

Two-Factor Authentication adds an extra layer of security to your account.

**Enabling 2FA:**
1. Go to **Email Settings** (in your user menu)
2. Click "Enable 2FA"
3. 2FA is now active on your account

**Using 2FA:**
- When you login, you'll receive a 6-digit code via email
- Enter this code on the verification page
- Codes expire in **10 minutes**
- You must use a new code each time you login

**Disabling 2FA:**
1. Go to **Email Settings**
2. Click "Disable 2FA"
3. 2FA is now turned off

**Lost Access?**
- If you can't access your 2FA code, contact support at the contact form on the homepage

---

## Photo Management

### Uploading Photos

1. **Click "Upload Photo"** in your user menu or navigation
2. **Add Photo Title** (required, max 100 characters)
3. **Add Photo Description** (optional, max 500 characters)
4. **Select or Capture Photo:**
   - Click "Choose Photo" to upload from your device
   - Or use your camera/webcam to capture a photo
5. **Edit Your Photo** (optional):
   - Click "Edit Photo" to crop, rotate, or adjust your image
   - Use the tools to customize your photo
6. **Click "Upload"** to publish your photo

**Supported Formats:**
- JPG, JPEG, PNG, GIF, WebP
- Maximum file size: Check with your administrator
- Photos are stored safely on the server

### Viewing Your Photos

- Go to your **User Profile** to see all your photos
- Click on any photo to see the full-size image
- View the photo's **title**, **description**, **upload date**, **likes**, and **comments**

### Editing Photos

Currently, you can manage your photos by:
- Viewing full details and comments
- Reading feedback from other users
- Tracking likes and engagement

### Deleting Photos

**Note:** Photo deletion must be requested through the admin panel. Contact an administrator to remove a photo from your profile.

### Photo Privacy

- All photos you upload are public and visible to all users
- Your profile can be set to public or private (see Profile Settings)
- Uploaded photos respect copyright laws - only upload photos you own or have permission to share

---

## Social Features

### Liking Photos

**Like a Photo:**
1. Browse the gallery or visit a user's profile
2. Click the **heart icon** (❤️) on a photo
3. The heart will turn red and the like count will increase

**Unlike a Photo:**
- Click the red heart icon again to remove your like
- The count will decrease

**View Who Liked Your Photos:**
- Go to your profile to see engagement metrics
- Check notifications for recent likes on your photos

### Commenting on Photos

**Leave a Comment:**
1. Click on a photo to view details
2. Scroll to the **comments section**
3. Enter your comment in the text field
4. Click "Post Comment" or press Enter
5. Your comment will appear on the photo

**View Comments:**
- Comments appear below the photo
- See the commenter's name, profile picture, and timestamp
- Comments are in chronological order (oldest first)

**Commenting Guidelines:**
- Be respectful and constructive
- No spam, hate speech, or inappropriate content
- Comments are moderated by administrators
- Keep comments relevant to the photo

### Following Users

**Follow a Photographer:**
1. Visit their profile
2. Click the **"Follow"** button
3. You'll now see their photos on your home feed
4. You'll receive notifications about their new uploads

**Unfollow a Photographer:**
1. Visit their profile (or your following list)
2. Click the **"Following"** button
3. You'll no longer see their photos on your feed

**View Your Followers:**
- Go to your profile
- See the **"Followers"** count
- View a list of users following you

**View Who You're Following:**
- Go to your profile
- See the **"Following"** count
- View a list of photographers you follow

---

## User Profile

### Viewing Your Profile

1. Click your **username** in the navigation menu
2. Or go to `/profile.php`
3. See your profile information and all your photos

### Profile Information

Your profile displays:

- **Profile Picture:** Your avatar image
- **Cover Photo:** A large background image
- **Username:** Your unique identifier
- **Bio:** A short description of yourself
- **Photos Count:** How many photos you've uploaded
- **Followers Count:** How many users follow you
- **Following Count:** How many photographers you follow
- **Join Date:** When you created your account
- **Social Links:** Your Instagram, Twitter, Facebook, and website (optional)
- **Location:** Your city/location (optional)

### Viewing Other Profiles

1. Click on a **username** anywhere on the site
2. Or click on a **profile picture**
3. You can see their public information and photos
4. You can like their photos, comment, and follow them

### Profile Settings

Go to **Settings** or **Email Settings** to:
- Update your profile picture
- Update your cover photo
- Edit your bio
- Add social media links
- Add your location
- Set your profile to public or private
- Choose whether to show your email

---

## Settings

### Accessing Settings

1. Click on your **username** in the top navigation
2. Select **"Settings"** or **"Email Settings"**
3. Make your desired changes
4. Click "Save" to apply changes

### Email Settings

**Update Email Address:**
1. Go to **Email Settings**
2. Enter your new email address
3. Click "Update Email"
4. Verify your new email address by clicking the verification link
5. Your email is now updated

**Change Password:**
1. Go to **Email Settings**
2. Enter your current password
3. Enter your new password
4. Confirm your new password
5. Click "Change Password"

**Two-Factor Authentication:**
- Enable or disable 2FA (see Account Management section)

### Profile Settings

**Update Profile Picture:**
1. Go to **Settings**
2. Click "Choose Profile Picture"
3. Select an image from your device
4. Click "Upload"

**Update Cover Photo:**
1. Go to **Settings**
2. Click "Choose Cover Photo"
3. Select an image from your device
4. Click "Upload"

**Edit Bio:**
1. Go to **Settings**
2. Enter your bio in the text field (max 500 characters)
3. Click "Save"

**Add Social Links:**
1. Go to **Settings**
2. Add your social media URLs:
   - Instagram profile
   - Twitter profile
   - Facebook profile
   - Personal website
3. Click "Save"

**Privacy Settings:**
1. Go to **Settings**
2. Choose:
   - **Profile Privacy:** Public (anyone can view) or Private (followers only)
   - **Show Email:** Publicly display your email or keep it private
3. Click "Save"

---

## Search & Discovery

### Searching for Photos

1. Click the **search icon** in the navigation bar
2. Enter your search term (photo titles, descriptions, or usernames)
3. Press Enter or click "Search"
4. Browse the results

**Search Tips:**
- Search for photo titles (e.g., "Mountain Sunset")
- Search for photo descriptions (e.g., "landscape")
- Search for usernames (e.g., "john_photography")
- Results update in real-time as you type

### Browsing the Gallery

**Home Gallery:**
- View all public photos from all users
- See the latest uploads
- Photos displayed with title, photographer name, upload time
- See like and comment counts

**Sorting Options:**
- **Latest:** Most recently uploaded photos
- **Most Liked:** Photos with the most likes
- **Most Commented:** Photos with the most comments
- **Popular:** Trending photos (based on engagement)

### Discovering Photographers

1. Browse the gallery to find photos you like
2. Click the **photographer's name** or **profile picture**
3. View their profile and all their photos
4. Click **"Follow"** to see their future uploads

---

## Contact & Support

### Using the Contact Form

**Send a Message:**
1. Go to the homepage (index.php)
2. Scroll to the **"Contact Us"** section
3. Enter your information:
   - **Name:** Your full name
   - **Email:** Your email address
   - **Subject:** What is your message about?
   - **Message:** Your detailed message
4. Click **"Send Message"**
5. Your message will be sent to the administrators

**Response Time:**
- The admin team typically responds within 24-48 hours
- Check your email (and spam folder) for their reply

### Reporting Issues

If you encounter problems or want to report:
- Technical bugs
- Inappropriate photos or comments
- User harassment
- Copyright issues

**Contact the Admin:**
1. Use the contact form (see above)
2. Subject: "Report - [Issue Type]"
3. Provide details about the problem
4. Include photos or usernames if relevant
5. An administrator will investigate and respond

---

## Frequently Asked Questions

### Account & Registration

**Q: How do I verify my email?**
A: Check your email inbox for a message from LensCraft and click the verification link. The link expires in 24 hours.

**Q: What if I don't receive the verification email?**
A: Check your spam or junk folder. If you still don't receive it, try registering again with the same email address.

**Q: Can I change my username?**
A: Username changes are not currently available. If you need a different username, contact support.

**Q: Is my password secure?**
A: Yes. Passwords are encrypted with industry-standard hashing. Never share your password with anyone.

**Q: Should I enable 2FA?**
A: Yes! 2FA adds an extra security layer to your account. We recommend enabling it, especially if you follow other photographers or have a large follower base.

### Photos & Uploads

**Q: What file formats can I upload?**
A: JPG, JPEG, PNG, GIF, and WebP formats are supported.

**Q: Is there a file size limit?**
A: Yes, but the exact limit depends on your server configuration. If your photo is too large, you'll see an error message. Try compressing or resizing your image.

**Q: Can I edit a photo after uploading?**
A: We offer a built-in photo editor before upload where you can crop, rotate, and adjust your image. After uploading, contact an administrator if you need to replace a photo.

**Q: Can I delete my photos?**
A: Contact an administrator with your photo IDs to request deletion. They can remove photos from your account.

**Q: Are my photos backed up?**
A: Photos are stored on the server. We recommend keeping copies of your original images on your device or cloud storage.

### Social Features

**Q: Can I unlike or delete a comment?**
A: Once a like or comment is posted, you can unlike it or request a comment deletion by contacting support.

**Q: Can I send private messages?**
A: Direct messaging is not currently available. You can communicate through photo comments or the contact form.

**Q: What does "follow" do?**
A: Following a photographer means you'll see their new photos on your home gallery feed, and you can monitor their latest uploads.

**Q: Can I see who viewed my photos?**
A: Currently, LensCraft doesn't track individual views. However, you can see likes and comments to gauge engagement.

### Privacy & Security

**Q: Is my profile public?**
A: By default, yes. You can make your profile private in settings so only followers can see your information.

**Q: Who can see my email?**
A: By default, your email is private. You can choose to display it publicly in settings.

**Q: Can I delete my account?**
A: Contact an administrator with your request. They can help you delete your account and associated data.

**Q: What happens to my photos if I delete my account?**
A: Contact support to discuss photo retention and deletion options before deleting your account.

---

## Troubleshooting

### Login Issues

**"Invalid email/username or password" error:**
- Verify you entered your credentials correctly
- Passwords are case-sensitive
- Ensure Caps Lock is off
- Reset your password if you forgot it

**"Email not verified" message:**
- Check your email inbox for a verification link
- Click the link to verify your email
- Check spam/junk folders
- Request a new verification email if needed

**"2FA code is invalid" error:**
- Ensure you entered the 6-digit code correctly
- Codes expire after 10 minutes, so request a new one
- Check that you're entering the most recent code

**Can't login with correct credentials:**
- Try clearing your browser cache and cookies
- Try a different browser
- Contact support if the problem persists

### Upload Issues

**"File type not supported" error:**
- Ensure your file is JPG, JPEG, PNG, GIF, or WebP
- Files must be images, not documents or other types
- Check the file extension (case-sensitive on some systems)

**"File size exceeds limit" error:**
- Compress your image using an image editor
- Reduce the resolution/dimensions of the photo
- Use an online image compression tool

**"Upload failed" error:**
- Check your internet connection
- Ensure your file is not corrupted
- Try uploading a different image to test
- Contact support if the error persists

**Photo doesn't appear after upload:**
- Refresh the page to see if it loads
- Clear your browser cache
- Wait a few seconds for the upload to process
- Contact support if the photo is still missing

### Performance Issues

**Pages loading slowly:**
- Check your internet connection
- Clear browser cache and cookies
- Disable browser extensions that might slow loading
- Try using a different browser
- Wait during peak usage times

**Photos not displaying:**
- Refresh the page
- Check your internet connection
- Try a different browser
- Clear your cache
- Contact support if images still don't load

### General Issues

**"Server error" or blank page:**
- Refresh the page
- Clear your browser cache
- Try a different browser
- Check if the site is down (visit homepage)
- Contact support with error details

**Links not working:**
- Ensure you're clicking the correct link
- Check that JavaScript is enabled in your browser
- Try copying the URL directly into your address bar
- Contact support to report broken links

**Can't find a feature:**
- Check the navigation menu
- Refer back to this guide for feature locations
- Features might be in Settings or your user menu
- Contact support if you can't locate a specific feature

---

## Best Practices

### Profile Security

✅ **Do:**
- Use a strong, unique password
- Enable Two-Factor Authentication
- Keep your email address current
- Update your password regularly
- Log out from shared computers

❌ **Don't:**
- Share your password with anyone
- Use the same password on multiple sites
- Click suspicious links in emails
- Enable 2FA code sharing with others

### Photo Sharing

✅ **Do:**
- Only upload photos you own or have permission to use
- Provide descriptive titles and descriptions
- Respect copyright and intellectual property
- Give credit when featuring other artists' styles
- Keep photos appropriate for all audiences

❌ **Don't:**
- Upload copyrighted photos without permission
- Violate others' privacy by uploading them without consent
- Upload adult or inappropriate content
- Spam other users' photos with self-promotion
- Upload the same photo multiple times

### Community Participation

✅ **Do:**
- Be respectful and constructive in comments
- Give genuine feedback on other photographers' work
- Follow photographers whose work inspires you
- Engage positively with the community
- Report inappropriate content to administrators

❌ **Don't:**
- Post spam, promotional links, or advertisements
- Harass, threaten, or insult other users
- Post hateful or discriminatory content
- Plagiarize others' photo descriptions or comments
- Use multiple accounts to inflate engagement

---

## Getting Help

### Contact Support

If you need assistance:

1. **Use the Contact Form:** Go to the homepage and use the "Contact Us" section
2. **Provide Details:** Describe your issue clearly and include relevant information
3. **Check Your Email:** Watch for a response from our support team
4. **Be Patient:** We typically respond within 24-48 hours

### Contact Information

- **Email:** [Check Contact Settings for admin email]
- **Contact Form:** Available on the homepage
- **Support Hours:** [Check with your administrator]

---

## Additional Resources

### Tips for Better Photography

- **Lighting:** Use natural light when possible
- **Composition:** Follow the rule of thirds for balanced images
- **Details:** Focus on interesting details and unique perspectives
- **Editing:** Use photo editing tools before uploading
- **Consistency:** Build a cohesive visual style in your portfolio

### Recommended Tools

- **Image Editing:** GIMP, Photoshop, Canva, Pixlr
- **Image Compression:** TinyPNG, ImageOptim, Compressor.io
- **Photo Hosting:** Upload to multiple platforms for backup
- **Inspiration:** Browse LensCraft gallery regularly to discover trends

---

## Version Information

- **Application:** LensCraft Photo Web Platform
- **Last Updated:** December 2024
- **Guide Version:** 1.0

For the most current information and updates, visit the website regularly and check your notifications.

---

**Welcome to the LensCraft community! Happy photographing! 📷**
