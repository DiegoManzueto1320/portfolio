document.addEventListener('DOMContentLoaded', function(){
  // Chargement dynamique des projets si présence d'un container
  const projectsContainer = document.getElementById('projects-container');
  if(projectsContainer){
    fetch('projects/projects.json')
      .then(r=>r.json())
      .then(data=>{
        window._projects = data.projects || [];
        renderProjects(window._projects);
        setupFilters(window._projects);
      })
      .catch(err=>{ projectsContainer.innerHTML = '<p>Impossible de charger les projets.</p>'; console.error(err); });
  }

  function renderProjects(list){
    const container = document.getElementById('projects-container');
    if(!container) return;
    container.innerHTML = '';
    list.forEach(p=>{
      const card = document.createElement('article');
      card.className = 'project-card';
      card.innerHTML = `
        <img src="${p.image||'assets/images/projects/placeholder.svg'}" alt="${escapeHtml(p.title)}">
        <h3>${escapeHtml(p.title)}</h3>
        <div class="project-meta">${escapeHtml(p.category)} · ${escapeHtml(p.technologies.join(', '))}</div>
        <p>${escapeHtml(p.excerpt)}</p>
        <p><a href="projects/${p.slug}.html">Voir le projet</a></p>
      `;
      container.appendChild(card);
    });
  }

  function setupFilters(list){
    const select = document.getElementById('projects-filter');
    if(!select) return;
    const cats = Array.from(new Set(list.map(p=>p.category)));
    select.innerHTML = '<option value="">Tous</option>' + cats.map(c=>`<option>${c}</option>`).join('');
    select.addEventListener('change', ()=>{
      const v = select.value;
      if(!v) renderProjects(list);
      else renderProjects(list.filter(p=>p.category===v));
    });
  }

  // Contact form submission with enhanced validation
  const contactForm = document.getElementById('contact-form');
  if(contactForm){
    // Real-time character counter for message field
    const messageField = document.getElementById('message');
    if(messageField){
      messageField.addEventListener('input', function(){
        const charCount = document.getElementById('char-count');
        if(charCount) charCount.textContent = this.value.length;
      });
    }

    // Form submission handler
    contactForm.addEventListener('submit', function(e){
      e.preventDefault();
      
      // Clear previous error messages
      document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
      
      // Validate form client-side first
      if(!validateContactForm()) return;
      
      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Envoi en cours...';
      
      const data = new FormData(contactForm);
      
      fetch('contact.php', { 
        method: 'POST', 
        body: data,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(r => {
          if(!r.ok) throw new Error('Réponse serveur invalide');
          return r.json();
        })
        .then(res => {
          const notice = document.getElementById('contact-notice');
          if(res.success){ 
            notice.className = 'notice success show'; 
            notice.textContent = res.message || 'Message envoyé avec succès. Merci !'; 
            contactForm.reset();
            document.getElementById('char-count').textContent = '0';
            window.scrollTo({ top: notice.offsetTop - 100, behavior: 'smooth' });
          }
          else { 
            notice.className = 'notice error show'; 
            notice.textContent = res.message || 'Erreur lors de l\'envoi.'; 
          }
        })
        .catch(err => {
          const notice = document.getElementById('contact-notice');
          notice.className = 'notice error show'; 
          notice.textContent = 'Erreur réseau. Veuillez vérifier votre connexion et réessayer.';
          console.error('Erreur:', err);
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        });
    });

    // Form validation function (simplified and professional)
    function validateContactForm(){
      let isValid = true;

      // Validate name
      const name = document.getElementById('name');
      const nameError = document.getElementById('name-error');
      if(!name.value.trim()){
        nameError.textContent = 'Le nom est requis.';
        isValid = false;
      } else if(name.value.trim().length < 2){
        nameError.textContent = 'Le nom est trop court.';
        isValid = false;
      } else {
        nameError.textContent = '';
      }

      // Validate email
      const email = document.getElementById('email');
      const emailError = document.getElementById('email-error');
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if(!email.value.trim()){
        emailError.textContent = 'L\'email est requis.';
        isValid = false;
      } else if(!emailRegex.test(email.value.trim())){
        emailError.textContent = 'Email invalide.';
        isValid = false;
      } else {
        emailError.textContent = '';
      }

      // Validate subject
      const subject = document.getElementById('subject');
      const subjectError = document.getElementById('subject-error');
      if(!subject.value.trim()){
        subjectError.textContent = 'L\'objet est requis.';
        isValid = false;
      } else if(subject.value.trim().length < 3){
        subjectError.textContent = 'L\'objet est trop court.';
        isValid = false;
      } else {
        subjectError.textContent = '';
      }

      // Validate message
      const message = document.getElementById('message');
      const messageError = document.getElementById('message-error');
      if(!message.value.trim()){
        messageError.textContent = 'Le message est requis.';
        isValid = false;
      } else if(message.value.trim().length < 10){
        messageError.textContent = 'Le message doit contenir au moins 10 caractères.';
        isValid = false;
      } else {
        messageError.textContent = '';
      }

      // Validate privacy checkbox
      const privacy = document.getElementById('privacy');
      const privacyError = document.getElementById('privacy-error');
      if(!privacy.checked){
        privacyError.textContent = 'Vous devez accepter le traitement de vos données.';
        isValid = false;
      } else {
        privacyError.textContent = '';
      }

      return isValid;
    }

    // Real-time validation on blur (name, email, subject, message)
    const inputs = contactForm.querySelectorAll('input, textarea');
    inputs.forEach(input => {
      input.addEventListener('blur', function(){
        const id = this.id;
        const val = this.value.trim();

        if(id === 'name'){
          const error = document.getElementById('name-error');
          if(!val) error.textContent = 'Le nom est requis.';
          else if(val.length < 2) error.textContent = 'Au moins 2 caractères.';
          else error.textContent = '';
        }
        if(id === 'email'){
          const error = document.getElementById('email-error');
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if(!val) error.textContent = 'L\'email est requis.';
          else if(!emailRegex.test(val)) error.textContent = 'Email invalide.';
          else error.textContent = '';
        }
        if(id === 'subject'){
          const error = document.getElementById('subject-error');
          if(!val) error.textContent = 'L\'objet est requis.';
          else if(val.length < 3) error.textContent = 'Au moins 3 caractères.';
          else error.textContent = '';
        }
        if(id === 'message'){
          const error = document.getElementById('message-error');
          if(!val) error.textContent = 'Le message est requis.';
          else if(val.length < 10) error.textContent = 'Au moins 10 caractères.';
          else error.textContent = '';
        }
      });
    });
  }

  function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
});
