@extends('layouts.app')

@section('seoMeta')
{!! view('components.seo-meta', [
    'title' => 'Politica de Confidențialitate | lista-firme.info',
    'description' => 'Politica de confidențialitate a lista-firme.info: ce date prelucrăm, de ce și ce drepturi ai conform GDPR.',
    'canonical' => route('legal.privacy'),
])->render() !!}
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-3xl mx-auto">
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                Politica de <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">Confidențialitate</span>
            </h1>
            <p class="mt-2 text-sm text-slate-500">Ultima actualizare: {{ date('d.m.Y') }}</p>
            <p class="mt-4 text-slate-300 leading-relaxed">
                Această politică explică, în cuvinte simple, ce date prelucrăm, de ce și ce drepturi ai.
            </p>
        </div>

        <div class="space-y-10 text-slate-300 leading-relaxed">
            {{-- 1. Operatorul --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">1. Cine este operatorul de date</h2>
                <p class="mb-4">
                    Operatorul de date cu caracter personal pentru lista-firme.info, în sensul Regulamentului UE 2016/679 (GDPR), este:
                </p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">RTI Global Services SRL</strong></span>
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
                        <span>E-mail pentru solicitări GDPR: <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a></span>
                    </li>
                </ul>
            </section>

            {{-- 2. Ce date prelucrăm --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">2. Ce date prelucrăm</h2>

                <h3 class="text-base font-semibold text-white mb-3">2.1. Datele vizitatorilor site-ului</h3>
                <p class="mb-3">
                    Când vizitezi lista-firme.info, colectăm automat:
                </p>
                <ul class="space-y-2 list-none mb-4">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Date tehnice:</strong> adresa IP, tipul de browser, sistemul de operare;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Date de utilizare:</strong> paginile accesate, durata vizitei.</span>
                    </li>
                </ul>
                <p class="mb-4">
                    Aceste date sunt folosite exclusiv pentru funcționarea și îmbunătățirea site-ului.
                </p>
                <p class="mb-6">
                    <strong class="text-white">Temeiul legal:</strong> interesul legitim de a menține și îmbunătăți serviciul (Art. 6 alin. 1 lit. f din Regulamentul UE 2016/679 — GDPR).
                </p>

                <h3 class="text-base font-semibold text-white mb-3">2.2. Datele publice despre societăți comerciale și persoanele asociate</h3>
                <p class="mb-3">
                    lista-firme.info sincronizează periodic, normalizează, indexează și afișează informații din registre publice și baze de date publice ale statului, inclusiv:
                </p>
                <ul class="space-y-2 list-none mb-4">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>denumirea, CUI-ul, numărul de înregistrare, sediul social și starea firmelor;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>numele asociaților și administratorilor societăților comerciale;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>codul CAEN și obiectul de activitate.</span>
                    </li>
                </ul>
                <p class="mb-4">
                    Aceste date provin exclusiv din surse publice oficiale sau registre publice (ANAF, ONRC, Monitorul Oficial, data.gov.ro). Nu colectăm date personale din surse private și nu adăugăm informații private despre persoane.
                </p>
                <p class="mb-4">
                    <strong class="text-white">Temeiul legal</strong> pentru prelucrarea acestor date:
                </p>
                <ul class="space-y-2 list-none mb-4">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Interesul legitim</strong> (Art. 6 alin. 1 lit. f GDPR) — facilitarea accesului la informații publice, verificarea partenerilor comerciali și transparența mediului de afaceri;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span>caracterul public al surselor oficiale, conform Legii nr. 265/2022, Legii nr. 31/1990 și Legii nr. 544/2001.</span>
                    </li>
                </ul>
                <p>
                    Deoarece aceste date nu sunt obținute direct de la persoanele vizate, această secțiune funcționează și ca informare conform Art. 14 GDPR. Informarea individuală a fiecărei persoane menționate în registre publice ar presupune un efort disproporționat raportat la volumul datelor și la caracterul deja public al surselor.
                </p>
            </section>

            {{-- 3. Cui transmitem datele --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">3. Cui transmitem datele</h2>
                <p class="mb-3">
                    Nu vindem baze de date private, date confidențiale sau date personale obținute din surse nepublice.
                </p>
                <p>
                    Datele tehnice ale vizitatorilor pot fi prelucrate de furnizori de servicii care ne ajută cu găzduirea și infrastructura. Acești furnizori prelucrează datele în baza unor acorduri conforme cu GDPR.
                </p>
            </section>

            {{-- 4. Cât timp păstrăm datele --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">4. Cât timp păstrăm datele</h2>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Datele tehnice ale vizitatorilor:</strong> maximum 26 de luni;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Datele publice din registrul comerțului și Monitorul Oficial:</strong> sincronizate periodic din sursele oficiale și reflectă starea acestor surse la momentul ultimei sincronizări.</span>
                    </li>
                </ul>
            </section>

            {{-- 5. Drepturile tale --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">5. Drepturile tale</h2>
                <p class="mb-3">Conform GDPR (Regulamentul UE 2016/679), ai următoarele drepturi:</p>
                <ul class="space-y-2 list-none">
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul de acces</strong> — poți afla ce date avem despre tine;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul la rectificare</strong> — poți cere corectarea datelor incorecte;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul la ștergere</strong> — poți cere ștergerea datelor, în condițiile prevăzute de lege;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul la restricționarea prelucrării</strong> — poți cere limitarea modului în care folosim datele;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul la opoziție</strong> — te poți opune prelucrării datelor (Art. 21 GDPR);</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul la portabilitate</strong> — poți cere o copie a datelor într-un format structurat;</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-violet-400 shrink-0">•</span>
                        <span><strong class="text-white">Dreptul de a depune plângere</strong> — te poți adresa autorității de supraveghere (vezi secțiunea 8).</span>
                    </li>
                </ul>
                <p class="mt-4">
                    <strong class="text-white">Cum îți exerciți drepturile:</strong> trimite un e-mail la
                    <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a>
                    cu numele tău complet, o descriere a cererii și o modalitate prin care putem verifica identitatea ta. Răspundem în maximum 30 de zile.
                </p>
                <p class="mt-4">
                    <strong class="text-white">O precizare importantă despre asociați și administratori:</strong> identitatea asociaților și a administratorilor unei societăți comerciale este, prin lege, informație publică obligatorie — publicată în Monitorul Oficial Partea a IV-a și în registrul comerțului conform Legii nr. 31/1990 și Legii nr. 265/2022. lista-firme.info afișează aceste date exact așa cum apar la sursa oficială, fără a adăuga sau inferi nimic. Nu putem modifica registrul comerțului sau Monitorul Oficial și nu putem șterge datele de la sursa oficială. Dacă dorești modificarea acestor date la sursă, cererea trebuie adresată direct la ONRC.
                </p>
            </section>

            {{-- 6. Cookie-uri --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">6. Cookie-uri</h2>
                <p>
                    lista-firme.info folosește cookie-uri strict necesare pentru funcționarea site-ului, care nu necesită consimțământ (de exemplu, cookie-ul de sesiune și tokenul CSRF). Nu folosim cookie-uri de analiză sau de urmărire de la terți.
                </p>
            </section>

            {{-- 7. Securitatea datelor --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">7. Securitatea datelor</h2>
                <p>
                    Aplicăm măsuri tehnice și organizatorice pentru protejarea datelor, inclusiv conexiune securizată (HTTPS/TLS) și limitarea ratei de acces pentru prevenirea utilizării abuzive.
                </p>
            </section>

            {{-- 8. Contact și autoritatea de supraveghere --}}
            <section>
                <h2 class="text-xl font-bold text-white mb-4">8. Contact și autoritatea de supraveghere</h2>
                <p class="mb-4">
                    Pentru exercitarea drepturilor sau întrebări:
                    <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a>
                </p>
                <p>
                    <strong class="text-white">Autoritatea Națională de Supraveghere a Prelucrării Datelor cu Caracter Personal (ANSPDCP):</strong><br>
                    B-dul G-ral. Gheorghe Magheru 28-30, Sector 1, București<br>
                    Website: <a href="https://www.dataprotection.ro" target="_blank" rel="noopener noreferrer" class="text-violet-300 hover:text-violet-200 transition-colors">www.dataprotection.ro</a><br>
                    E-mail: <a href="mailto:anspdcp@dataprotection.ro" class="text-violet-300 hover:text-violet-200 transition-colors">anspdcp@dataprotection.ro</a>
                </p>
            </section>
        </div>
    </div>
</div>
@endsection
