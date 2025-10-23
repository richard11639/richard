// assets/js/site.js
document.addEventListener('DOMContentLoaded', function(){
  // Simple slider (auto + arrows)
  const slides = document.querySelectorAll('.slider .slides img');
  let idx = 0;
  function show(i){
    slides.forEach(s=>s.classList.remove('active'));
    slides[i].classList.add('active');
    document.querySelector('.slider .slides').style.transform = `translateX(${-i*100}%)`;
  }
  document.getElementById('next').addEventListener('click', ()=>{
    idx = (idx+1) % slides.length; show(idx);
  });
  document.getElementById('prev').addEventListener('click', ()=>{
    idx = (idx-1+slides.length) % slides.length; show(idx);
  });
  setInterval(()=>{ idx=(idx+1)%slides.length; show(idx); }, 5000);

  // Modal preview for gallery & room images
  const modal = document.getElementById('modal');
  const modalImg = document.getElementById('modal-img');
  const modalClose = document.getElementById('modal-close');
  document.querySelectorAll('.gallery-img, .img-link').forEach(el=>{
    el.addEventListener('click', (e)=>{
      e.preventDefault();
      const src = (el.tagName.toLowerCase()==='img') ? el.src : el.getAttribute('data-img');
      modalImg.src = src;
      modal.classList.add('show');
    });
  });
  modalClose.addEventListener('click', ()=> modal.classList.remove('show'));
  modal.addEventListener('click', (e)=> { if(e.target===modal) modal.classList.remove('show'); });

});
