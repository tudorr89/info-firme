@extends('layouts.app')

@section('seoMeta')
{!! view('components.seo-meta', [
    'title' => 'Termeni și Condiții | lista-firme.info',
    'description' => 'Termenii și condițiile de utilizare ai lista-firme.info. Date publice, fără garanții, fără afiliere cu ANAF sau ONRC.',
    'canonical' => route('legal.terms'),
])->render() !!}
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-3xl mx-auto">
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                Termeni și <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">Condiții</span>
            </h1>
            <p class="mt-2 text-sm text-slate-500">Ultima actualizare: {{ date('d.m.Y') }}</p>
        </div>

        <div class="space-y-10 text-slate-300 leading-relaxed">
            {{-- 1. Cine suntem --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">1. Cine suntem</h2>
                <p class="mb-4">
                    lista-firme.info este operat de <strong class="text-white">RTI Global Services SRL</strong>, societate înmatriculată în România:
                </p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>Denumire: <strong class="text-white">RTI Global Services SRL</strong></span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>CUI: <strong class="text-white">51859625</strong></span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>Nr. Reg. Com.: <strong class="text-white">J2025037467000</strong></span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>E-mail: <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a></span>
                    </li>
                </ul>
                <p class="mt-4">
                    Pe parcursul acestor termeni, „noi", „operatorul" sau „lista-firme.info" desemnează RTI Global Services SRL.
                </p>
                <p class="mt-4">
                    Site-ul <strong class="text-white">nu este afiliat, asociat sau autorizat</strong> de ANAF, ONRC sau orice altă instituție a statului român. Este un proiect independent.
                </p>
            </section>

            {{-- 2. Ce este lista-firme.info --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">2. Ce este lista-firme.info</h2>
                <p class="mb-4">
                    lista-firme.info este o interfață web care afișează date publice preluate din surse oficiale ale statului român, inclusiv:
                </p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>Date din Registrul Comerțului, puse la dispoziție de Oficiul Național al Registrului Comerțului (ONRC) conform Legii nr. 265/2022 privind registrul comerțului;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>API-urile publice ale Agenției Naționale de Administrare Fiscală (ANAF);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>Informații disponibile pe portalul de date deschise data.gov.ro.</span>
                    </li>
                </ul>
                <p class="mt-4">
                    Scopul site-ului este de a oferi cetățenilor și profesioniștilor o modalitate mai ușoară de a accesa, căuta și corela informații publice la care au dreptul conform legii.
                </p>
            </section>

            {{-- 3. Sursa și natura datelor --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">3. Sursa și natura datelor</h2>
                <p class="mb-4">
                    Toate datele afișate pe lista-firme.info sunt <strong class="text-white">informații de interes public</strong>, puse la dispoziție de instituțiile statului. Temeiul legal pentru accesul la aceste date:
                </p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Legea nr. 544/2001</strong> privind liberul acces la informațiile de interes public;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Legea nr. 109/2007</strong> privind reutilizarea informațiilor din instituțiile publice;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Legea nr. 265/2022</strong> privind registrul comerțului, care stabilește caracterul public al informațiilor din registru;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Legea nr. 31/1990</strong> (Legea societăților), republicată, conform căreia datele de identificare ale asociaților și administratorilor sunt informații de interes public.</span>
                    </li>
                </ul>
                <p class="mt-4">
                    lista-firme.info nu creează registre oficiale noi și nu certifică datele în numele statului. Datele sunt preluate și sincronizate periodic din sursele publice menționate mai sus, fiind normalizate, indexate și prezentate într-o formă mai ușor de căutat.
                </p>
            </section>

            {{-- 4. Ce poți face --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">4. Ce poți face pe acest site</h2>
                <p class="mb-4">Poți folosi lista-firme.info pentru:</p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>consultarea informațiilor publice despre societăți comerciale (date de identificare, cod CAEN, sediu social, reprezentanți legali);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>căutarea companiilor după denumire sau CUI;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>consultarea de agregări și statistici calculate din date publice.</span>
                    </li>
                </ul>
            </section>

            {{-- 5. Ce nu poți face --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">5. Ce nu poți face pe acest site</h2>
                <p class="mb-4">Prin utilizarea lista-firme.info, te angajezi să <strong class="text-white">nu</strong>:</p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>folosești datele în scopuri ilegale, inclusiv hărțuire, amenințare, discriminare sau fraudă;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>colectezi automat și în masă date de pe site (scraping) în scopuri comerciale sau care încalcă drepturile altor persoane;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>folosești datele pentru trimiterea de comunicări comerciale nesolicitate (spam);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>folosești date cu caracter personal obținute prin site fără un temei legal propriu sau cu încălcarea drepturilor persoanelor vizate;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>prezinți informațiile de pe site ca fiind emise sau validate de o instituție a statului;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>încerci să interferezi cu funcționarea normală a site-ului.</span>
                    </li>
                </ul>
            </section>

            {{-- 6. Limitarea răspunderii --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">6. Limitarea răspunderii</h2>
                <p class="mb-4">
                    lista-firme.info afișează date preluate din surse publice oficiale <strong class="text-white">așa cum sunt</strong>. Nu garantăm:
                </p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>că datele sunt actualizate în timp real (actualizarea depinde de sursa oficială);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>că datele sunt complete sau fără erori (erorile pot exista în sursa originală);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>că site-ul va fi disponibil permanent și fără întreruperi.</span>
                    </li>
                </ul>
                <p class="mt-4">
                    Pentru informații cu caracter oficial sau juridic, consultă direct sursele oficiale: anaf.ro, onrc.ro, data.gov.ro.
                </p>
            </section>

            {{-- 7. Proprietate intelectuală --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">7. Proprietate intelectuală</h2>
                <p>
                    Codul sursă, designul și elementele grafice ale lista-firme.info sunt proprietatea operatorului, conform Legii nr. 8/1996.
                </p>
                <p class="mt-4">
                    Datele publice afișate pe site nu sunt proprietatea noastră — ele sunt informații de interes public conform legislației menționate la secțiunea 3.
                </p>
            </section>

            {{-- 8. Modificarea termenilor --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">8. Modificarea termenilor</h2>
                <p>
                    Acești termeni pot fi modificați oricând. Modificările intră în vigoare la data publicării pe site. Continuarea utilizării site-ului după publicarea modificărilor constituie acceptarea noilor termeni.
                </p>
            </section>

            {{-- 9. Legea aplicabilă --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">9. Legea aplicabilă</h2>
                <p>
                    Acești termeni sunt guvernați de legislația din România. Orice litigiu va fi soluționat de instanțele competente din România.
                </p>
            </section>

            {{-- 10. Contact --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">10. Contact</h2>
                <p>
                    Pentru orice întrebare legată de acești termeni sau de funcționarea site-ului, ne poți scrie la
                    <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a>.
                </p>
            </section>
        </div>
    </div>
</div>
@endsection
