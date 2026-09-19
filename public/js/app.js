document.addEventListener('DOMContentLoaded',()=>{
    document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('#sidebar').classList.toggle('open'));
    document.querySelectorAll('[data-modal]').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.modal)?.showModal()));
    document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));

    const startFlatpickr=()=>{
        document.querySelectorAll('input[type="date"],input[type="datetime-local"],input[type="month"]').forEach(input=>{
            const pickerType=input.type;
            input.type='text';
            const options={disableMobile:true,allowInput:true,altInput:true};
            if(pickerType==='datetime-local')Object.assign(options,{enableTime:true,dateFormat:'Y-m-d\\TH:i',altFormat:'d M Y, h:i K',time_24hr:false,altInputClass:'form-control flatpickr-input',onReady:(_,__,instance)=>instance.altInput.placeholder=input.placeholder||'Select date & time'});
            else if(pickerType==='month')Object.assign(options,{dateFormat:'Y-m',altFormat:'F Y',plugins:[new monthSelectPlugin({shorthand:true,dateFormat:'Y-m',altFormat:'F Y'})],onReady:(_,__,instance)=>instance.altInput.placeholder=input.placeholder||'Select month'});
            else Object.assign(options,{dateFormat:'Y-m-d',altFormat:'d M Y',onReady:(_,__,instance)=>instance.altInput.placeholder=input.placeholder||'Select date'});
            flatpickr(input,options);
        });
    };
    const addStylesheet=href=>{if(!document.querySelector(`link[href="${href}"]`)){const link=document.createElement('link');link.rel='stylesheet';link.href=href;document.head.appendChild(link);}};
    const loadScript=src=>new Promise((resolve,reject)=>{const existing=document.querySelector(`script[src="${src}"]`);if(existing){if(existing.dataset.loaded==='true')resolve();else existing.addEventListener('load',resolve,{once:true});return;}const script=document.createElement('script');script.src=src;script.addEventListener('load',()=>{script.dataset.loaded='true';resolve();},{once:true});script.addEventListener('error',reject,{once:true});document.head.appendChild(script);});
    addStylesheet('/vendor/flatpickr/flatpickr.min.css');
    addStylesheet('/vendor/flatpickr/monthSelect.css');
    loadScript('/vendor/flatpickr/flatpickr.min.js').then(()=>loadScript('/vendor/flatpickr/monthSelect.js')).then(startFlatpickr);

    const startSweetAlerts=()=>{
        const toast=Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3500,timerProgressBar:true});
        document.querySelectorAll('.alert').forEach(alert=>{
            const icon=alert.classList.contains('success')?'success':'error';
            toast.fire({icon,title:alert.textContent.trim()});
            alert.remove();
        });

        document.addEventListener('submit',event=>{
            const form=event.target;
            if(!(form instanceof HTMLFormElement)||form.dataset.sweetAlertConfirmed==='true')return;
            const inline=form.getAttribute('onsubmit')||'';
            const match=inline.match(/confirm\((['"])(.*?)\1\)/);
            const message=form.dataset.confirm||match?.[2];
            if(!message)return;
            event.preventDefault();
            event.stopImmediatePropagation();
            Swal.fire({
                icon:'warning',
                title:form.dataset.confirmTitle||'Are you sure?',
                text:message,
                showCancelButton:true,
                confirmButtonText:'Yes, continue',
                cancelButtonText:'Cancel',
                confirmButtonColor:getComputedStyle(document.documentElement).getPropertyValue('--purple').trim()||'#7357b5',
                reverseButtons:true
            }).then(result=>{
                if(!result.isConfirmed)return;
                form.dataset.sweetAlertConfirmed='true';
                form.removeAttribute('onsubmit');
                form.requestSubmit();
            });
        },true);
    };

    if(window.Swal)startSweetAlerts();
    else{
        const css=document.createElement('link');
        css.rel='stylesheet';css.href='/vendor/sweetalert2/sweetalert2.min.css';document.head.appendChild(css);
        const script=document.createElement('script');
        script.src='/vendor/sweetalert2/sweetalert2.all.min.js';script.onload=startSweetAlerts;document.head.appendChild(script);
    }

    const timer=document.querySelector('[data-start]');
    if(timer){
        const start=new Date(timer.dataset.start),breaks=Number(timer.dataset.break||0);
        const tick=()=>{const seconds=Math.max(0,Math.floor((Date.now()-start)/1000)-breaks);timer.textContent=`${Math.floor(seconds/3600)}h ${String(Math.floor(seconds%3600/60)).padStart(2,'0')}m`;};
        tick();setInterval(tick,30000);
    }
});
