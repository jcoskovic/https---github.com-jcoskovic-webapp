# 📝 Abbrevio - Modern Abbreviation Management System

> Napredna full-stack aplikacija za upravljanje skraćenicama s personaliziranim ML preporukama, kompletnim autentifikacionim sustavom i real-time funkcionalnostima.




``` Korisnici za testiranje
 Administrator
Email: admin@abbrevio.test  
Password: admin1234

 Standardni korisnik  
Email: user@abbrevio.test
Password: user1234

 Moderator
Email: moderator@abbrevio.test
Password: moderator1234
```

### ⚡ 5-minutno pokretanje
```bash
git clone <repository-url>
cd https---github.com-jcoskovic-abbrevio  # ili cd <ime-foldera> ako si klonirao pod drugim imenom
docker compose build               # Build svih servisa
docker compose up -d               # Pokreni sve servise
# Čekaj ~2 minute da se sve servisi podignu
docker compose exec backend php artisan migrate:fresh --seed #komanda za migracije i slanje seed podataka u bazu

docker compose exec backend php artisan jwt:secret # komanda za postavljanje novog, sigurnog jwt ključa, testna verzija je postavljena već, no u slučaju produkcije nije siguran taj ključ


# Ako backend kontejner ima probleme sa vendor mapom:
cd backend
cp .env.example .env               # Stvori konfiguraciju
mkdir -Force bootstrap\cache       # Stvori cache direktorij
mkdir -Force storage\framework\sessions, storage\framework\views, storage\framework\cache  # Stvori framework direktorije
composer install                   # Instaliraj PHP ovisnosti  
php artisan key:generate           # Generiraj app ključ
cd ..
docker-compose restart backend     # Restartaj backend


# Aplikacija će biti dostupna na:
# Frontend: http://localhost:4200
# Backend API: http://localhost:8000
# ML Service: http://localhost:5001
# phpMyAdmin: http://localhost:8080
# Mailpit (email testing): http://localhost:8025
```

---

## O projektu

Abbrevio je moderna web aplikacija dizajnirana za organizaciju i upravljanje skraćenicama s naglaskom na korisničko iskustvo i napredne funkcionalnosti. Aplikacija omogućava korisnicima logiranje, podijeljeni su korisnici u tri različita razreda obični korisnici, moderatori (imaju mogućnost odobravanja skraćenica i brisanja) te admini (sve što i moderatori + upravljanje korisnicima, mogu običnog korisnika unaprijediti u moderatora i moderatora vratiti u običnog korisnika ali ne mogu unaprijediti u admina). Obični korisnici mogu pregledati, tražiti skraćenice praviti ispis u pdf-u, komentirati davati pozitivnu ili negativnu ocjenu. Svaka skraćenica nakon što je dodana ide na čekanje te je mora moderator ili admin u svom panelu odobriti da bi se prikazivala ostalim korisnicima, ovako se sprječava prekomjerno objavljivanje i spamanje. Klikom na izvezi u pdf otvara se prozor u kojem je moguće odabrati jednu ili više skraćenica te se generira pdf sa tim skraćenicama, generiranje pdf-a je odrađeno putem laravel bladeviewa. Na ekranu se također nalazi button za generiranje ML prijedloga. ML servis, napravljen u pythonu, koristi TF-IDF za analizu podataka, Cosine Similarity za preporuke te Random Forrest za klasifikaciju. Na osnovu podataka o korisniku, njegovih akcija s ostalim skraćenicama boduje skraćenice koje on nije pregledavao te ih boduje i prikazuje mu 6 s najvecim brojem bodova. Također, kada korisnik dodaje novu skraćenicu može upisati naziv i zatražiti prijedlog značenja. Prijedlog značenja se povlači putem Nactem Acromine API-ja koji koristi značenja unesena u Britanski National Centre for Text Mining (NACTEM). Napravljeno je da se na homepageu prikaze prvotno 10 skraćenica, te da se učitava još 10 po 10 na klik gumba.  



##  Arhitektura sustava

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │   ML Service   │
│   Angular 19    │◄──►│   Laravel 11    │◄──►│   Python 3.9   │
│                 │    │                 │    │   + Flask      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│     MySQL      │◄─────────────┘
                        │   Database     │
                        └─────────────────┘
```

### Technology Stack
- **Frontend**: Angular 19+ s TypeScript, SCSS, Angular Material
- **Backend**: Laravel 11 s PHP 8.2+, JWT authentication  
- **Database**: MySQL 8.0+ s optimiziranim indeksima
- **ML Service**: Python 3.9+, Flask, scikit-learn, GROQ API
- **DevOps**: Docker, Docker Compose, multi-environment setup
- **Testing**: PHPUnit (backend), Jasmine/Karma (frontend)
- **Email**: Multi-provider support (Gmail, SendGrid, Mailgun, Mailpit)

## � Ključne funkcionalnosti

###  Upravljanje korisnicima
- ✅ **JWT autentifikacija** s refresh token mehanizmom
- ✅ **Role-based access control** (admin/user permissions)
- ✅ **Email verifikacija** i password reset workflow
- ✅ **Secure session management** s tokenizacijom

###  Upravljanje skraćenicama
- ✅ **CRUD operacije** s validation i error handling
- ✅ **Advanced search & filtering** po kategorijama, statusu
- ✅ **Real-time glasovanje** (upvote/downvote) sustav
- ✅ **Trending algoritam** s weighted scoring
- ✅ **Komentiranje sustav** s real-time updates

###  Machine Learning integracija
- ✅ **Personalizirane preporuke** na temelju user behaviour
- ✅ **Content-based filtering** s GROQ API
- ✅ **Fallback mehanizmi** za offline ML service
- ✅ **Real-time training** s user interaction data

###  Security & Performance
- ✅ **Input sanitization** i SQL injection protection
- ✅ **Rate limiting** za API endpoints
- ✅ **Database optimization** s composite indexes
- ✅ **Response time < 20ms** za većinu endpoints

###  Export & Analytics
- ✅ **PDF export** s custom formatting
- ✅ **CSV export** za data analysis
- ✅ **Admin dashboard** s korisničkim statistikama
- ✅ **API documentation** s Swagger/OpenAPI

##  Struktura projekta

```
abbrevio/
├── frontend/           # Angular 19 aplikacija
│   ├── src/app/       # Komponente, servisi, guards
│   ├── src/assets/    # Statički resursi
│   └── nginx.conf     # Production server konfiguracija
├── backend/           # Laravel 11 API
│   ├── app/           # Controllers, Models, Services
│   ├── database/      # Migrations, seeders, factories
│   ├── tests/         # 45+ testova (Unit/Feature/Integration)
│   └── routes/        # API route definitions
├── ml-service/        # Python ML mikroservis
│   ├── app.py         # Flask application
│   ├── models/        # ML model storage
│   └── requirements.txt
├── docker/            # Docker multi-stage builds
│   ├── backend/       # PHP-FPM optimizacija
│   ├── frontend/      # Nginx s Angular
│   └── ml-service/    # Python runtime
├── docs/              # Arhitektura i API dokumentacija
└── docker-compose.yml # Multi-environment orchestracija
```


## �🔧 Instalacija

### 🐳 Docker (Preporučeno)

Najjednostavniji način pokretanja cijele aplikacije:

```bash
# Kloniraj repozitorij
git clone <repository-url>
cd https---github.com-jcoskovic-abbrevio  # ili cd <ime-foldera> ako si klonirao pod drugim imenom

# Pokreni sve servise
docker-compose up -d




# Aplikacija će biti dostupna na:
# Frontend: http://localhost:4200
# Backend API: http://localhost:8000
# ML Service: http://localhost:5001
# phpMyAdmin: http://localhost:8080
# Mailpit (email testing): http://localhost:8025
```

### 📧 Email Setup

Za funkcionalnost zaboravljene lozinke, aplikacija podržava slanje email-ova kroz različite providere:

#### Gmail SMTP (Brzo testiranje)
Dodaj u backend `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Abbrevio"
```

#### SendGrid (Produkcija)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Abbrevio"
```

#### Mailgun (Produkcija)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.yourdomain.com
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Abbrevio"
```

#### Mailpit (Development/Testing)
Za development okruženje, Mailpit je već uključen u Docker setup:
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=test@abbrevio.dev
MAIL_FROM_NAME="Abbrevio Dev"
```


### Ručna instalacija

#### Preduvjeti
- Node.js 18+
- PHP 8.2+
- Composer
- MySQL 8.0+
- Python 3.9+

#### Quick Start

```bash
# Backend setup
cd backend
composer install
cp .env.example .env
# Dodaj GROQ_API_KEY u .env datoteku
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan serve

# Frontend setup
cd ../frontend
npm install
ng serve

# ML Service setup
cd ../ml-service
pip install -r requirements.txt
# Provjeri da backend .env ima GROQ_API_KEY postavljen
python app.py
```

### 🌐 Deployment strategy

**NAPOMENA**: Ova aplikacija je trenutno konfigurirana za development. Za production deployent:

#### 1. Environment konfiguracija
```bash
# Kopiraj i uredi production configs
cp backend/.env.example backend/.env.production
cp frontend/src/environments/environment.ts frontend/src/environments/environment.prod.ts
```

#### 2. Security checklist
- [ ] Promijeni APP_KEY u backend/.env
- [ ] Postavi CORS_ALLOWED_ORIGINS na production domenu
- [ ] Konfigurirati HTTPS certifikate
- [ ] Postaviti rate limiting za API
- [ ] Konfigurirati email provider (SendGrid/Mailgun)
- [ ] Postaviti MySQL production bazu
- [ ] Konfigurirati backup strategiju

#### 3. Performance optimizacije
- [ ] Enable Redis za cache
- [ ] Konfigurirati CDN za statičke resurse
- [ ] Database indexing optimizacije
- [ ] Monitoring i logging (ElasticSearch/Kibana)

---

