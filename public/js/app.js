document.addEventListener('DOMContentLoaded',()=>{
    document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('#sidebar').classList.toggle('open'));
    const topbar=document.querySelector('.topbar');
    if(topbar&&!['/','/admin','/admin/'].includes(window.location.pathname)){
        topbar.classList.add('has-page-back');
        const back=document.createElement('button');
        back.type='button';back.className='page-back';back.setAttribute('aria-label','Go back');back.title='Back';
        back.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
        back.addEventListener('click',()=>{const sameOrigin=document.referrer&&new URL(document.referrer).origin===window.location.origin;if(sameOrigin&&history.length>1)history.back();else window.location.href='/admin';});
        const heading=topbar.querySelector(':scope > div');
        if(heading)topbar.insertBefore(back,heading);
    }
    document.querySelectorAll('[data-modal]').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.modal)?.showModal()));
    document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));

    const breakIcon=title=>{
        const value=String(title||'').toLowerCase();
        const svg=paths=>`<svg viewBox="0 0 24 24" aria-hidden="true">${paths}</svg>`;
        if(/meal|lunch|food|dinner/.test(value))return svg('<path d="M7 3v7m3-7v7M5 3v5a3.5 3.5 0 0 0 7 0V3m-3.5 8v10M17 3v18m0-18c2 2 2 6 0 8"/>');
        if(/coffee|tea|chai/.test(value))return svg('<path d="M5 8h12v6a5 5 0 0 1-5 5h-2a5 5 0 0 1-5-5V8Zm12 2h2a2 2 0 0 1 0 4h-2M8 3v2m4-2v2"/>');
        if(/prayer|namaz|salah/.test(value))return svg('<path d="M18 4a7 7 0 1 0 2 12 8 8 0 1 1-2-12ZM5 21h14M8 17h8"/>');
        if(/meeting|call|conference/.test(value))return svg('<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a5 5 0 0 1 10 0v2m3-11a3 3 0 0 1 0 6m5 5v-2a4 4 0 0 0-3-3"/>');
        if(/wash|toilet|restroom/.test(value))return svg('<path d="M12 3C9 7 7 9 7 13a5 5 0 0 0 10 0c0-4-2-6-5-10Z"/>');
        if(/smoke|cigarette/.test(value))return svg('<path d="M3 15h14v4H3zm16 0h2v4h-2zM7 11h8a3 3 0 0 0 0-6"/>');
        if(/down|system|power|technical/.test(value))return svg('<path d="M12 2v10m-6.4-6A9 9 0 1 0 18.4 6"/>');
        if(/extra|rest|second|pause|break/.test(value))return svg('<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>');
        return svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>');
    };
    document.querySelectorAll('[data-break-icon]').forEach(icon=>{icon.innerHTML=breakIcon(icon.dataset.breakIcon);});

    document.querySelectorAll('[data-leave-type]').forEach(select=>{
        const form=select.closest('form');
        const certificateField=form?.querySelector('[data-medical-certificate]');
        const certificateInput=certificateField?.querySelector('input[type="file"]');
        const syncCertificateField=()=>{
            const isMedical=select.value==='medical';
            if(certificateField)certificateField.hidden=!isMedical;
            if(certificateInput){certificateInput.disabled=!isMedical;certificateInput.required=false;if(!isMedical)certificateInput.value='';}
        };
        select.addEventListener('change',syncCertificateField);
        syncCertificateField();
    });

    document.querySelectorAll('[data-range-filter]').forEach(form=>{
        const select=form.querySelector('[data-range-select]');
        const syncRangeFields=()=>{
            const range=select?.value;
            form.querySelectorAll('[data-range-month]').forEach(field=>{field.hidden=range!=='month';field.querySelectorAll('input').forEach(input=>input.disabled=range!=='month');});
            form.querySelectorAll('[data-range-custom]').forEach(field=>{field.hidden=range!=='custom';field.querySelectorAll('input').forEach(input=>input.disabled=range!=='custom');});
        };
        select?.addEventListener('change',syncRangeFields);
        syncRangeFields();
    });

    const startFlatpickr=()=>{
        document.querySelectorAll('input[type="date"],input[type="datetime-local"],input[type="month"]').forEach(input=>{
            const pickerType=input.type;
            const dialog=input.closest('dialog');
            input.type='text';
            const options={disableMobile:true,allowInput:true,altInput:true};
            if(dialog){options.static=true;dialog.classList.add('has-flatpickr');}
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
        const start=new Date(timer.dataset.start),closedBreaks=Number(timer.dataset.break||0),activeBreak=timer.dataset.activeBreak?new Date(timer.dataset.activeBreak):null;
        const pad=value=>String(value).padStart(2,'0');
        const tick=()=>{const activeBreakSeconds=activeBreak?Math.max(0,Math.floor((Date.now()-activeBreak)/1000)):0;const seconds=Math.max(0,Math.floor((Date.now()-start)/1000)-closedBreaks-activeBreakSeconds);timer.textContent=`${pad(Math.floor(seconds/3600))}:${pad(Math.floor(seconds%3600/60))}:${pad(seconds%60)}`;};
        tick();setInterval(tick,1000);
    }
});
