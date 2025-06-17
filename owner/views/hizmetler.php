<style>
    /* Akordiyon Menü için Özel Stiller */
    .accordion-item {
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .accordion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        cursor: pointer;
        background-color: white;
    }
    .accordion-header:hover {
        background-color: var(--gray-50);
    }
    .accordion-header h3 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 600;
    }
    .accordion-header .price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-600);
    }
    .accordion-icon {
        transition: transform 0.3s ease;
    }
    .accordion-item.open .accordion-icon {
        transform: rotate(180deg);
    }
    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out, padding 0.4s ease-out;
        background-color: white;
        padding: 0 1.5rem;
    }
    .accordion-item.open .accordion-content {
        max-height: 1000px; /* İçeriğin sığacağı kadar büyük bir değer */
        padding: 1.5rem;
        border-top: 1px solid var(--gray-200);
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .service-gallery img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .service-gallery figcaption {
        text-align: center;
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-top: 0.5rem;
    }
</style>

<div class="page-content">
    <h2 class="text-2xl font-bold mb-6">İşinizi Büyütmenize Yardımcı Olacak Ek Hizmetler</h2>

    <div id="services-accordion">
        <!-- 1. Hazır Web Sitesi -->
        <div class="accordion-item">
            <div class="accordion-header">
                <div>
                    <i data-feather="monitor"></i>
                    <h3>Hazır Web Sitesi</h3>
                    <p class="text-sm text-gray-500 mt-1">1 günde teslim, online rezervasyon motoru dahil.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="price">9,999₺</span>
                    <i data-feather="chevron-down" class="accordion-icon"></i>
                </div>
            </div>
            <div class="accordion-content">
                <p>Tesisinize özel, modern ve mobil uyumlu web siteniz sadece 1 günde hazır! Property Hub ile tam entegre çalışan online rezervasyon motoru ile doğrudan rezervasyon alın, komisyon ödemeyin.</p>
                <div class="service-gallery">
                    <figure>
                        <img src="https://placehold.co/600x400/3B82F6/white?text=Tema+1" alt="Hazır Web Sitesi Teması 1">
                        <figcaption>Modern Tema</figcaption>
                    </figure>
                    <figure>
                        <img src="https://placehold.co/600x400/16A34A/white?text=Tema+2" alt="Hazır Web Sitesi Teması 2">
                        <figcaption>Doğal Tema</figcaption>
                    </figure>
                    <figure>
                        <img src="https://placehold.co/600x400/CA8A04/white?text=Tema+3" alt="Hazır Web Sitesi Teması 3">
                        <figcaption>Lüks Tema</figcaption>
                    </figure>
                </div>
                <div class="text-right mt-4">
                    <a href="mailto:destek@propertyhub.com?subject=Hazır Web Sitesi Teklifi" class="btn btn-primary">Teklif Al</a>
                </div>
            </div>
        </div>

        <!-- 2. Özel Web Sitesi -->
        <div class="accordion-item">
            <div class="accordion-header">
                <div>
                    <i data-feather="monitor"></i>
                    <h3>Özel Web Sitesi</h3>
                    <p class="text-sm text-gray-500 mt-1">Hayalinizdeki tasarımı gerçeğe dönüştürelim.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="price">19,999₺</span>
                    <i data-feather="chevron-down" class="accordion-icon"></i>
                </div>
            </div>
            <div class="accordion-content">
                <p>Tamamen size özel, markanızın kimliğini yansıtan, benzersiz bir web sitesi tasarımı ve geliştirmesi. Sınırsız revizyon hakkı ve online rezervasyon motoru dahildir. Projeniz için özel bir ekip sizinle çalışır.</p>
                 <div class="text-right mt-4">
                    <a href="mailto:destek@propertyhub.com?subject=Özel Web Sitesi Teklifi" class="btn btn-primary">Teklif Al</a>
                </div>
            </div>
        </div>
        
        <!-- Diğer Hizmetler... -->
        <div class="accordion-item">
            <div class="accordion-header">
                <div>
                    <i data-feather="file-plus"></i>
                    <h3>Google Sheet Entegrasyonu</h3>
                     <p class="text-sm text-gray-500 mt-1">Rezervasyonlarınızı anında e-tablonuzda görün.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="price">2,999₺</span>
                    <i data-feather="chevron-down" class="accordion-icon"></i>
                </div>
            </div>
            <div class="accordion-content">
                <p>Tüm rezervasyon ve müsaitlik verilerinizi, belirleyeceğiniz bir Google Sheets dosyası ile çift yönlü senkronize edelim. Hem Property Hub'dan hem de e-tablodan yaptığınız değişiklikler anında birbirine yansısın.</p>
                 <div class="text-right mt-4">
                    <a href="mailto:destek@propertyhub.com?subject=Google Sheet Entegrasyonu Teklifi" class="btn btn-primary">Teklif Al</a>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header">
                <div>
                    <i data-feather="film"></i>
                    <h3>Medya Desteği</h3>
                     <p class="text-sm text-gray-500 mt-1">Profesyonel fotoğraf ve video çekimi.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="price">6,999₺</span>
                    <i data-feather="chevron-down" class="accordion-icon"></i>
                </div>
            </div>
             <div class="accordion-content">
                <p>Tesisinizi en iyi şekilde yansıtacak profesyonel fotoğraf ve drone ile video çekimi hizmetleri. Web sitenizde ve sosyal medyada kullanabileceğiniz yüksek kaliteli görsellerle daha fazla misafir çekin.</p>
                 <div class="text-right mt-4">
                    <a href="mailto:destek@propertyhub.com?subject=Medya Desteği Teklifi" class="btn btn-primary">Teklif Al</a>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header">
                <div>
                    <i data-feather="instagram"></i>
                    <h3>Sosyal Medya Yönetimi</h3>
                     <p class="text-sm text-gray-500 mt-1">Aylık içerik ve reklam yönetimi paketi.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="price">13,999₺</span>
                    <i data-feather="chevron-down" class="accordion-icon"></i>
                </div>
            </div>
             <div class="accordion-content">
                <p>Sosyal medya hesaplarınızı profesyonel ekibimiz yönetsin. Aylık 3 Reels videosu, 12 profesyonel post ve 12 hikaye paylaşımı ile marka bilinirliğinizi ve etkileşiminizi artırın. Reklam kampanyalarınızın planlanması ve optimizasyonu da pakete dahildir.</p>
                 <div class="text-right mt-4">
                    <a href="mailto:destek@propertyhub.com?subject=Sosyal Medya Yönetimi Teklifi" class="btn btn-primary">Teklif Al</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const accordionItems = document.querySelectorAll('.accordion-item');

        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            header.addEventListener('click', () => {
                // Tıklanan item dışındaki tüm item'ları kapat
                accordionItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('open')) {
                        otherItem.classList.remove('open');
                    }
                });
                
                // Tıklanan item'ı aç/kapat
                item.classList.toggle('open');
            });
        });
    });
</script>
