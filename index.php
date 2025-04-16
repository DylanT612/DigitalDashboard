<!-- 
Login page(index)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Haar: haark@csp.edu
Ty Steinbach: 
1/25/25
Revisions: 
1/25/25: Dylan Theis created php db connection and html doc outline
02/04/25: Ty Steinbach added PHP to ensure reset_password functionality when needed
02/05/25: Keagan Haar created a styling CSS
02/16/25: Ty Steinbach changed hash() to password_hash() for security and changed comparison to password_verify, changed table to users
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="indexStyles.css" />

  <!-- GSAP and ScrollTrigger -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
</head>

<body>

  <section class="tile white">
    <h1 class="animate-title">Welcome to My Digital Dashboard</h1>
  </section>

  <section class="tile black">
    <h1 class="animate-section">About Us</h1>
    <p class="animate-section">We offer a personalized digital homebase for our users.</p>
    <p class="animate-section">Users can: View local weather, chat with friends, customize their personal calendar and so much more!</p>
  </section>

  <section class="tile white">
    <h1 class="animate-section">Where To Start?</h1>
    <p class="animate-section">Login or create an account with the link below.</p>
    <p class="animate-section">Thank you for using our service!</p>
    <p><a href="login.php" class="link-button">Login</a></p>
  </section>

  <section class="tile black">
    <h1 class="animate-section">About Us</h1>
    <p class="animate-section">Contact Information:</p>
    <p class="animate-section">Ty Steinbach: steinbat1@csp.edu </p>
    <p class="animate-section">Dylan Theis: theisd@csp.edu</p>
    <p class="animate-section">Keagan Haar: haark@csp.edu</p>
    <p class="animate-section">Website Link</p>
    <p class="animate-section">&copy; 2025 My Digital Dashboard. All rights reserved.</p>

  </section>
  <script>
    gsap.registerPlugin(ScrollTrigger);

    // Animate title on page load
    gsap.from(".animate-title", {
      opacity: 0,
      y: -80,
      duration: 1.5,
      ease: "expo.out"
    });

    // Animate each section's heading and paragraph
    document.querySelectorAll(".tile").forEach((section, i) => {
      const heading = section.querySelector("h1");
      const paragraph = section.querySelector("p");

      if (heading && paragraph) {
        const tl = gsap.timeline({
          scrollTrigger: {
            trigger: section,
            start: "top 80%",
            toggleActions: "play none none reverse"
          }
        });

        tl.from(heading, {
          opacity: 0,
          y: 50,
          duration: 1,
          ease: "power3.out"
        }).from(paragraph, {
          opacity: 0,
          y: 40,
          duration: 1,
          ease: "power3.out"
        }, "-=0.5");
      }
    });

    gsap.utils.toArray(".tile").forEach((tile, i) => {
        gsap.to(tile, {
            scrollTrigger: {
            trigger: tile,
            start: "top bottom",
            end: "bottom top",
            scrub: true
            }
        });
    });
  </script>
</body>
</html>