# E-Commerce GraphQL API

Kurumsal ölçekte bir e-ticaret platformu için GraphQL tabanlı backend API. Laravel, Lighthouse GraphQL, PostgreSQL, Redis, Elasticsearch ve Docker kullanarak geliştirilmiştir.

## 🚀 Özellikler

### Temel İşlevler
- ✅ **Kullanıcı Yönetimi**: Kayıt, giriş, profil yönetimi (Laravel Passport ile)
- ✅ **Adres Yönetimi**: CRUD işlemleri, varsayılan adres belirleme
- ✅ **Ürün Yönetimi**: CRUD işlemleri, stok takibi
- ✅ **Hemen Satın Al**: Sepetsiz direkt satın alma akışı
- ✅ **Ödeme İşlemleri**: Fake payment gateway entegrasyonu
- ✅ **Sipariş Yönetimi**: Sipariş oluşturma, görüntüleme, iptal etme
- ✅ **Ürün Arama**: Elasticsearch ile tam metin arama ve filtreleme

### Teknik Özellikler
- 🔐 Laravel Passport ile OAuth2 authentication
- 📊 GraphQL API (Lighthouse paketi)
- 🔍 Elasticsearch ile gelişmiş arama
- 💾 PostgreSQL veritabanı
- ⚡ Redis ile cache ve queue yönetimi
- 🐳 Docker ile tam konteynerizasyon
- 🔒 Race condition kontrolü ile stok yönetimi
- 📦 Transaction yönetimi ile veri bütünlüğü

## 📋 Gereksinimler

- Docker ve Docker Compose
- Git

## 🛠️ Kurulum

### 1. Projeyi Klonlayın

```bash
git clone <repository-url>
cd example-ecommerce
```

### 2. Environment Dosyasını Oluşturun

```bash
cp .env.example src/.env
```

### 3. Docker Konteynerlerini Başlatın

```bash
docker-compose up -d
```

### 4. Bağımlılıkları Kurun

```bash
docker-compose exec php composer install
```

### 5. Uygulama Anahtarını Oluşturun

```bash
docker-compose exec php php artisan key:generate
```

### 6. Veritabanı Migration'larını Çalıştırın

```bash
docker-compose exec php php artisan migrate
```

### 7. Passport Keys Oluşturun

```bash
docker-compose exec php php artisan passport:keys
docker-compose exec php php artisan passport:client --personal
```

### 8. Seed Verilerini Yükleyin

```bash
docker-compose exec php php artisan db:seed
```

### 9. Elasticsearch Index'ini Oluşturun

```bash
docker-compose exec php php artisan tinker
# Tinker içinde:
$es = app(\App\Services\ElasticsearchService::class);
$es->createIndex();
$es->bulkIndexProducts();
exit
```

## 🌐 Servisler ve Portlar

| Servis | Port | Açıklama |
|--------|------|----------|
| Nginx | 8080 | API endpoint |
| PostgreSQL | 5432 | Veritabanı |
| Redis | 6379 | Cache & Queue |
| Elasticsearch | 9200, 9300 | Arama motoru |

**API Endpoint**: `http://localhost:8080/graphql`

## 🔑 Test Kullanıcıları

Seed verilerinde oluşturulan test kullanıcıları:

```
Email: test@example.com
Password: password123

Email: admin@example.com
Password: admin123
```

## 📖 GraphQL API Kullanımı

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
  }
}
```

#### Ödeme İşlemi

```graphql
mutation {
  processPayment(input: {
    order_id: 1
    payment_method: "credit_card"
  }) {
    id
    transaction_id
    status
    amount
    paid_at
  }
}
```

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

## 🏗️ Proje Yapısı

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
│   │       └── ElasticsearchService.php
│   ├── database/
│   │   ├── migrations/         # Veritabanı migration'ları
│   │   └── seeders/            # Seed verileri
│   └── graphql/
│       └── schema.graphql      # GraphQL şema tanımları
├── docker-compose.yml          # Docker Compose yapılandırması
└── README.md
```

## 🔧 Mimari Kararlar

### Race Condition Önleme
Stok güncelleme işlemlerinde race condition'ları önlemek için:
- Database transaction'ları kullanılmıştır
- `lockForUpdate()` ile pessimistic locking uygulanmıştır
- Stok kontrolü ve güncelleme atomik olarak yapılmıştır

### Ödeme Akışı
1. Kullanıcı "Hemen Satın Al" ile sipariş oluşturur
2. Stok rezerve edilir (decrement)
3. Sipariş `pending` statüsünde oluşturulur
4. Payment gateway çağrılır (fake implementation)
5. Başarılı ise sipariş `processing`, başarısız ise `failed` olur
6. İptal durumunda stok geri eklenir (increment)

### Elasticsearch Stratejisi
- Ürünler PostgreSQL'de master data olarak tutulur
- Elasticsearch arama için kullanılır (replica)
- Ürün oluşturma/güncelleme sonrası index güncellenir
- Tam metin arama, filtreleme ve fuzzy search desteklenir

## 🧪 Test

### GraphQL Playground
GraphQL sorguları test etmek için `http://localhost:8080/graphql` adresini ziyaret edin.

### Queue Worker
Background job'ları çalıştırmak için:

```bash
docker-compose exec php php artisan queue:work
```

## 📊 Veritabanı Şeması

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

## 🐛 Sorun Giderme

### Port Çakışması
Eğer 8080, 5432, 6379 veya 9200 portları kullanımdaysa, `docker-compose.yml` dosyasında portları değiştirin.

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

## 📝 Notlar

- Bu proje **development** amaçlıdır
- Production için ek güvenlik önlemleri alınmalıdır:
  - SSL/TLS sertifikaları
  - Rate limiting
  - CORS yapılandırması
  - Güvenli secret key management
  - Gerçek payment gateway entegrasyonu

## 🤝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'feat: add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

## 👥 İletişim

Sorularınız için issue açabilirsiniz.
