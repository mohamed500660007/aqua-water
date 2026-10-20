# بنية المشروع — Aqua Water Dashboard

لوحة تحكم Laravel 12 + Blade + Alpine (RTL). الكود منظّم حسب **المجال (Domain)** أولًا،
ثم طبقة العرض (HTTP/Blade)، مع طبقة مشتركة صغيرة.

## الطبقات

```
app/
├── Domain/                     ← منطق العمل — لا يعرف شيئًا عن HTTP أو Blade
│   ├── Customers/
│   │   ├── Models/             Customer, CustomerPhone   (Eloquent + العلاقات + scopes الاستعلام)
│   │   ├── Enums/              CustomerStatus            (الحالات + تسمياتها العربية)
│   │   ├── Data/               CustomerData              (DTO: عقد مدخلات الإنشاء/التعديل)
│   │   └── Services/           CustomerService           (قواعد العمل: transaction، توليد الكود، مزامنة الهواتف)
│   ├── Users/                  Models / Enums / Data / Services
│   ├── Products/               Product, ProductStatus, ProductData, ProductService
│   ├── Inventory/              Warehouse, StockBalance, StockMovement
│   │                           StockService (كل تغيير في الرصيد يمر منه) + InsufficientStockException
│   ├── Maintenance/            MaintenancePlan, MaintenancePlanItem + MaintenancePlanService
│   └── Sales/                  SalesContract (+ Items / Installments / MaintenanceItems)
│                               ContractPricing (تسعير البنود) + SalesContractService
│
├── Http/                       ← طبقة التسليم: تترجم الطلب إلى DTO وتعيد View
│   ├── Controllers/Customers/     CustomerController     (نحيف: استعلام قائمة + استدعاء الخدمة + إعادة توجيه)
│   ├── Controllers/Users/         UserController
│   ├── Controllers/Maintenance/   MaintenancePlanController
│   ├── Controllers/Inventory/     InventoryController (الأرصدة) + StockMovementController (الحركات)
│   ├── Controllers/Sales/         SalesContractController
│   ├── Controllers/Products/      ProductController
│   ├── Controllers/               SettingsController
│   └── Requests/{Customers,Users,Products,Maintenance,Inventory,Sales}/  (تحقق + توحيد مدخلات + toData())
│
└── Support/                    ← نواة مشتركة بين المجالات
    ├── PhoneNumber.php         توحيد صيغة أرقام الهاتف المصرية
    ├── Digits.php              تحويل الأرقام العربية وتنظيفها
    ├── Lookups.php             إعدادات فقط: عناصر التنقل + قوائم اختيار (محافظات، أدوار، أقسام، فروع)
    └── ToneMap.php             خريطة ألوان نظام التصميم
```

```
resources/views/
├── layouts/app.blade.php       الهيكل + مخازن Alpine العامة
├── components/ui/*             نظام التصميم (dropdown, field, paginator, modal…)
├── partials/shell/*            الشريط الجانبي والهيدر والتنقل السفلي
└── pages/{contracts,customers,products,inventory,maintenance-plans,users}/
                                صفحات كل وحدة: index / create / edit / show / form
```

## اتجاه الاعتماد

```
Blade  →  Controller  →  Request → Data (DTO)  →  Service  →  Model
                                       ↑                        ↓
                                   Support (مشترك)          قاعدة البيانات
```

المجال لا يستورد أي شيء من `App\Http`، والواجهة لا تستدعي الموديلات مباشرة —
الـ Controller يمرّر للـ View كل ما تحتاجه (قوائم، حالات، سجلات).

## القواعد العملية عند إضافة وحدة جديدة

1. أنشئ `app/Domain/<Module>/` بأقسام `Models` و`Enums` و`Data` و`Services` حسب الحاجة.
2. المنطق الذي يلمس أكثر من جدول أو يحتاج transaction → في `Services`.
3. منطق الاستعلام (بحث/فلترة/ترتيب) → scopes داخل الموديل مع whitelist للأعمدة القابلة للترتيب.
4. التحقق وتوحيد المدخلات → Form Request، ثم `toData()` يحوّلها إلى DTO مكتوب الأنواع.
5. الصفحات في `resources/views/pages/<module>/` وتُعيد استخدام مكوّنات `components/ui`.
6. أضف اختبارات Feature تغطي: القائمة/البحث/الفلاتر/الترقيم + الإنشاء/التعديل/الحذف + التحقق.

## ترابط الوحدات

```
Products ──┬──> Inventory (StockBalance + StockMovement)
           │
           ├──> Maintenance (خطة واحدة: 7 شمعات بدوريات ثابتة لكل الفلاتر)
           │
           └──> Sales ──> Customers (العميل)
                     ──> Inventory  (صرف الكميات عبر StockService بحركات مرجعها العقد)
                     ──> Maintenance (نسخة ثابتة من بنود الخطة وقت التعاقد)
```

قواعد مهمة في هذا الترابط:

- **الرصيد لا يُعدَّل مباشرة أبدًا**: كل تغيير يمر عبر `StockService::record()` الذي يسجّل الحركة
  ويحدّث الرصيد في معاملة واحدة مع قفل الصف، ويمنع أي رصيد سالب.
- **أسعار بنود العقد تُقرأ من جدول المنتجات** وقت الحفظ ولا تأتي من الواجهة، وتُخزَّن كنسخة داخل البند.
- **تعديل العقد يحرّك المخزون بالفرق فقط** (زيادة = صرف، نقصان = رد)، والحذف يعيد الكميات كاملة.
- **شمعات خطة الصيانة تُنسخ في العقد** بمواعيد = تاريخ التركيب + دورية الشمعة، وتُعاد عند تغيير الخطة أو تصحيح تاريخ التركيب ما لم تتغير أي شمعة.
- **ملف الصيانة** = شمعات العقود التي لم تتم. كل شمعة تُغيَّر مرة واحدة (قطاعي أو جملة لكل الباقي) وتُخصم من عهدة الفني، وتفاصيل التغيير وعمولته تُحفظ على سطر الشمعة نفسه.

## لا توجد بيانات ثابتة

كل صفحة في اللوحة تقرأ من قاعدة البيانات. الصفحات التجريبية القديمة
(الرئيسية، الطلبات، التصنيفات، المدفوعات، التقسيط، الإشعارات، التقارير، الإعدادات)
حُذفت بالكامل مع مصفوفاتها، و`/dashboard` يفتح على الصفحة الرئيسية (`HomeController`): ترحيب وكل أقسام اللوحة مع مؤشرات سريعة.

البيانات التجريبية الوحيدة الباقية هي **بذور قاعدة البيانات** داخل `database/seeders/`
(موظفون، عملاء، منتجات برصيد افتتاحي، خطتا صيانة) — وهي مكانها الصحيح ولا يقرأها التطبيق.

## ما لم نُدخله عمدًا

- **Repositories وواجهات (interfaces)**: Eloquent نفسه هو طبقة الوصول للبيانات، وإضافة واجهة
  بتنفيذ واحد تضاعف الكود بلا فائدة حقيقية في هذا الحجم.
- **Entities منفصلة عن Eloquent + Mappers**: تتطلب طبقة تحويل كاملة مقابل مكسب نظري فقط.
- **Authentication / Policies**: النظام لا يحتوي على تسجيل دخول حتى الآن؛ عمود كلمة المرور
  موجود وجاهز، ويُضاف الـ Auth لاحقًا دون تغيير بنية المجال.

## أوامر مفيدة

```bash
composer dev          # تشغيل الخادم + الأصول
php artisan test      # كل الاختبارات
vendor/bin/pint       # تنسيق الكود (إعدادات المشروع في pint.json)
php artisan migrate:fresh --seed
```
