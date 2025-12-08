# E-Commerce GraphQL API

Kurumsal ölçekte bir e-ticaret platformu için GraphQL tabanlı backend API. Laravel, Lighthouse GraphQL, PostgreSQL, Redis, Elasticsearch ve Docker kullanarak geliştirilmiştir.

## Özellikler

### Temel İşlevler
- **Kullanıcı Yönetimi**: Kayıt, giriş, profil yönetimi (Laravel Passport ile)
- **Adres Yönetimi**: CRUD işlemleri, varsayılan adres belirleme
- **Ürün Yönetimi**: CRUD işlemleri, stok takibi
- **Hemen Satın Al**: Sepetsiz direkt satın alma akışı (Ödeme entegrasyonu ile)
- **Ödeme İşlemleri**: Fake payment gateway entegrasyonu (credit_card, debit_card, bank_transfer)
- **Sipariş Yönetimi**: Sipariş oluşturma, görüntüleme, iptal etme
- **Ürün Arama**: Elasticsearch ile tam metin arama ve filtreleme

### Teknik Özellikler
- Laravel Passport ile OAuth2 authentication (Token rotation ve refresh token desteği)
- GraphQL API (Lighthouse paketi)
- Elasticsearch ile gelişmiş arama
- PostgreSQL veritabanı
- Redis ile cache ve queue yönetimi
- Docker ile tam konteynerizasyon
- Race condition kontrolü ile stok yönetimi
- Transaction yönetimi ile veri bütünlüğü

## Gereksinimler

- Docker ve Docker Compose
- Git

## Kurulum

Projeyi klonladıktan sonra tek komutla kurulum yapabilirsiniz:

```bash
git clone <repository-url>
cd ecommerce-graphql
chmod +x setup.sh
./setup.sh
```

Bu script otomatik olarak:
- `.env` dosyasını oluşturur
- Docker container'ları build eder ve başlatır
- Composer bağımlılıklarını kurar
- Uygulama anahtarını oluşturur
- Veritabanı migration'larını çalıştırır
- Laravel Passport'u yapılandırır (OAuth keys ve personal access client)
- Test verilerini yükler
- Konfigürasyonu optimize eder

### Manuel Kurulum

Adım adım manuel kurulum yapmak isterseniz:

#### 1. Projeyi Klonlayın

```bash
git clone <repository-url>
cd ecommerce-graphql
```

#### 2. Environment Dosyasını Oluşturun

```bash
cp .env.example src/.env
```

#### 3. Docker Konteynerlerini Başlatın

```bash
docker-compose up -d --build
```

**Not**: Docker container'ı ilk başlatıldığında `docker-entrypoint.sh` script'i otomatik olarak çalışır ve aşağıdaki işlemleri yapar:
- Database bağlantısını bekler
- Migration'ları çalıştırır
- Passport keys'leri oluşturur
- Personal access client'ı oluşturur
- Seed verilerini yükler (development ortamında)
- Konfigürasyonu cache'ler

#### 4. (Opsiyonel) Manuel Passport Kurulumu

Eğer Passport client'ı oluşturulmamışsa manuel olarak oluşturabilirsiniz:

```bash
docker-compose exec php php artisan passport:keys
docker-compose exec php php artisan passport:client --personal
# veya seeder ile:
docker-compose exec php php artisan db:seed --class=PassportClientSeeder
```

#### 5. (Opsiyonel) Elasticsearch Index'ini Oluşturun

```bash
docker-compose exec php php artisan tinker
# Tinker içinde:
$es = app(\App\Services\ElasticsearchService::class);
$es->createIndex();
$es->bulkIndexProducts();
exit
```

## Servisler ve Portlar

| Servis | Port | Açıklama |
|--------|------|----------|
| Nginx | 8080 | API endpoint |
| PostgreSQL | 5433 | Veritabanı (host port) |
| Redis | 6379 | Cache & Queue |
| Elasticsearch | 9200, 9300 | Arama motoru |

**API Endpoint**: `http://localhost:8080/graphql`

## Test Kullanıcıları

Seed verilerinde oluşturulan test kullanıcıları:

```
Email: test@example.com
Password: password123

Email: admin@example.com
Password: admin123
```

## GraphQL API Kullanımı

### Authentication

#### Kayıt Olma

```graphql
mutation {
  register(input: {
    name: "John Doe"
    email: "john@example.com"
    password: "password123"
    password_confirmation: "password123"
  }) {
    access_token
    token_type
    expires_in
    user {
      id
      name
      email
    }
  }
}
```

#### Giriş Yapma

```graphql
mutation {
  login(input: {
    email: "test@example.com"
    password: "password123"
  }) {
    access_token
    token_type
    expires_in
    user {
      id
      name
      email
    }
  }
}
```

**Not**: Dönen `access_token`'ı sonraki isteklerde header olarak ekleyin:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

#### Token Yenileme

Access token'ın süresi dolduğunda refresh token kullanarak yeni token alabilirsiniz:

```graphql
mutation {
  refreshToken(input: {
    refresh_token: "YOUR_REFRESH_TOKEN"
  }) {
    access_token
    token_type
    expires_in
    refresh_token
  }
}
```

### Ürün İşlemleri

#### Ürünleri Listele

```graphql
query {
  products(first: 10) {
    data {
      id
      name
      price
      stock_quantity
      brand
      description
    }
    paginatorInfo {
      total
      currentPage
    }
  }
}
```

#### Ürün Ara (Elasticsearch)

```graphql
query {
  searchProducts(
    query: "iPhone"
    brand: "Apple"
    minPrice: 10000
    maxPrice: 70000
    inStock: true
    page: 1
    limit: 20
  ) {
    data {
      id
      name
      price
      stock_quantity
    }
    total
    page
    limit
  }
}
```

### Adres İşlemleri

#### Adres Ekleme

```graphql
mutation {
  createAddress(input: {
    title: "Ev"
    full_name: "John Doe"
    phone: "+90 555 123 4567"
    address_line_1: "Atatürk Cad. No:123"
    city: "Istanbul"
    postal_code: "34000"
    country: "Turkey"
    is_default: true
  }) {
    id
    title
    full_name
    is_default
  }
}
```

#### Adreslerimi Listele

```graphql
query {
  myAddresses {
    id
    title
    full_name
    phone
    city
    is_default
  }
}
```

### Satın Alma İşlemleri

#### Hemen Satın Al

```graphql
mutation {
  buyNow(input: {
    product_id: 1
    quantity: 1
    address_id: 1
    payment_method: "credit_card"
    notes: "Lütfen kapıya bırakın"
  }) {
    id
    order_number
    status
    total
    items {
      product_name
      quantity
      price
    }
    payment {
      transaction_id
      status
      amount
      payment_method
    }
  }
}
```

**Not**: `payment_method` değerleri: `credit_card`, `debit_card`, `bank_transfer`

#### Siparişlerimi Listele

```graphql
query {
  myOrders {
    id
    order_number
    status
    total
    items {
      product_name
      quantity
      price
    }
    payment {
      status
      transaction_id
    }
  }
}
```

#### Sipariş İptal Et

```graphql
mutation {
  cancelOrder(orderId: 1) {
    id
    order_number
    status
  }
}
```

## Proje Yapısı

```
ecommerce-graphql/
├── docker/                     # Docker yapılandırma dosyaları
│   ├── nginx/                  # Nginx Dockerfile ve config
│   └── php/                    # PHP-FPM Dockerfile ve config
├── src/                        # Laravel uygulama kodu
│   ├── app/
│   │   ├── GraphQL/
│   │   │   ├── Mutations/      # GraphQL mutation resolver'ları
│   │   │   └── Queries/        # GraphQL query resolver'ları
│   │   ├── Models/             # Eloquent modeller
│   │   └── Services/           # İş mantığı servisleri
│   │       ├── CheckoutService.php
│   │       ├── PaymentService.php
│   │       ├── TokenService.php
│   │       └── ElasticsearchService.php
│   ├── database/
│   │   ├── migrations/         # Veritabanı migration'ları
│   │   └── seeders/            # Seed verileri
│   └── graphql/
│       └── schema.graphql      # GraphQL şema tanımları
├── .env.example                # Environment değişkenleri örnek dosyası
├── docker-compose.yml          # Docker Compose yapılandırması
├── setup.sh                    # Otomatik kurulum script'i
├── E-Commerce-GraphQL-API.postman_collection.json  # Postman collection
└── README.md
```

## Mimari Kararlar

### Race Condition Önleme
Stok güncelleme işlemlerinde race condition'ları önlemek için:
- Database transaction'ları kullanılmıştır
- `lockForUpdate()` ile pessimistic locking uygulanmıştır
- Stok kontrolü ve güncelleme atomik olarak yapılmıştır

### Sipariş ve Ödeme Akışı
1. Kullanıcı "Hemen Satın Al" ile sipariş oluşturur
2. Stok rezerve edilir (decrement)
3. Sipariş `pending` statüsünde oluşturulur
4. PaymentService çağrılarak ödeme işlemi başlatılır
5. Ödeme başarılı ise:
   - Payment kaydı `completed` olarak oluşturulur
   - Order status `completed` olarak güncellenir
   - Transaction ID kaydedilir
6. Ödeme başarısız ise:
   - Payment kaydı `failed` olarak oluşturulur
   - Order status `failed` olarak güncellenir
   - Stok geri eklenir (increment)
7. İptal durumunda:
   - Stok geri eklenir (increment)
   - Tamamlanmış ödemeler `refunded` olarak işaretlenir
   - İptal sadece `pending` veya `processing` statüsündeki siparişlerde yapılabilir

### Elasticsearch Stratejisi
- Ürünler PostgreSQL'de master data olarak tutulur
- Elasticsearch arama için kullanılır (replica)
- Tam metin arama, filtreleme ve fuzzy search desteklenir
- Turkish analyzer desteği ile Türkçe karakterler için optimize edilmiştir
- Index'leme manuel olarak yapılmalıdır (ElasticsearchService kullanarak)

### Fake Payment Gateway
- Ödeme işlemleri simüle edilmektedir (production'da gerçek gateway entegre edilmelidir)
- %90 başarı oranı ile payment simülasyonu yapılır
- Desteklenen ödeme methodları: `credit_card`, `debit_card`, `bank_transfer`
- Her işlem için unique transaction ID oluşturulur
- Payment response data JSON olarak saklanır

## Test

### GraphQL Playground
GraphQL sorguları test etmek için `http://localhost:8080/graphql` adresini ziyaret edin.

### Postman Collection
Proje root dizininde `E-Commerce-GraphQL-API.postman_collection.json` dosyası bulunmaktadır. Bu dosyayı Postman'e import ederek hazır API isteklerini kullanabilirsiniz.

### Queue Worker
Background job'ları çalıştırmak için:

```bash
docker-compose exec php php artisan queue:work
```

## Veritabanı Şeması

### Tablolar
- `users`: Kullanıcılar
- `addresses`: Kullanıcı adresleri
- `products`: Ürünler
- `orders`: Siparişler
- `order_items`: Sipariş kalemleri
- `payments`: Ödeme kayıtları
- `oauth_*`: Laravel Passport tabloları

### İlişkiler
- User → hasMany → Address, Order
- Order → belongsTo → User, Address
- Order → hasMany → OrderItem
- Order → hasOne → Payment
- OrderItem → belongsTo → Product

## Sorun Giderme

### Passport Authentication Hatası
Eğer login sırasında "Personal access client not found" hatası alıyorsanız:

```bash
# Passport keys'leri oluşturun
docker-compose exec php php artisan passport:keys

# Personal access client oluşturun
docker-compose exec php php artisan passport:client --personal

# Veya seeder kullanın
docker-compose exec php php artisan db:seed --class=PassportClientSeeder
```

### Port Çakışması
Eğer 8080, 5433, 6379 veya 9200 portları kullanımdaysa, `docker-compose.yml` dosyasında portları değiştirin.

### Elasticsearch Hatası
Elasticsearch başlatma hatası alırsanız:

```bash
# Elasticsearch konteynerini yeniden başlatın
docker-compose restart elasticsearch

# Logları kontrol edin
docker-compose logs elasticsearch
```

### Migration Hatası
Migration hataları için:

```bash
# Migration'ları sıfırlayın
docker-compose exec php php artisan migrate:fresh --seed
```

### Container Başlatma Sorunları
Eğer container'lar düzgün başlamıyorsa:

```bash
# Container'ları durdurup temizleyin
docker-compose down

# Yeniden build edip başlatın
docker-compose up -d --build

# Container loglarını kontrol edin
docker-compose logs -f php
```

## Notlar

- Bu proje **development** amaçlıdır
- Production için ek güvenlik önlemleri alınmalıdır:
  - SSL/TLS sertifikaları
  - Rate limiting
  - CORS yapılandırması
  - Güvenli secret key management
  - Gerçek payment gateway entegrasyonu

## Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'feat: add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

## İletişim

Sorularınız için issue açabilirsiniz.
