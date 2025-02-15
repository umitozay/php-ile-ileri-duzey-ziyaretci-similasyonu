<?php
/**
 * Advanced Visitor Simulator
 *
 * Bu sınıf, cURL Multi kullanarak belirlenen hedef siteye eşzamanlı HTTP istekleri gönderir.
 * Her istek için rastgele user agent seçilir. Böylece, gerçek ziyaretçi davranışına yakın
 * bir trafik simülasyonu elde edilmek istenir.
 *
 * NOT: Bu yapı yalnızca test veya analiz amaçlı kullanılmalı; yapay trafik üretimi,
 * reklam gelirlerini haksız biçimde artırmak amacıyla kullanılması, arama motorları ve reklam
 * sağlayıcıları tarafından cezalandırılmanıza yol açabilir.
 */

class VisitorSimulator {
    private $targetUrl;
    private $numConcurrent;  // Aynı anda gönderilecek istek sayısı
    private $userAgents;     // Kullanılacak rastgele User-Agent dizisi

    public function __construct($targetUrl, $numConcurrent = 5) {
        $this->targetUrl = $targetUrl;
        $this->numConcurrent = $numConcurrent;
        $this->userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36',
            // İsteğe bağlı olarak daha fazla User-Agent ekleyebilirsin
        ];
    }

    // Rastgele bir User-Agent seçer
    private function getRandomUserAgent() {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    // Tek bir cURL oturumu oluşturur
    private function createCurlHandle() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Rastgele User-Agent ayarla
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getRandomUserAgent());
        // İsteğe bağlı: İstek zaman aşımı (örneğin 10 saniye)
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        return $ch;
    }

    /**
     * simulateVisitors()
     *
     * Bu metod, belirtilen sayıda ziyaret simülasyonu (HTTP isteği) gerçekleştirir.
     * - Eşzamanlı olarak numConcurrent sayıda istek gönderir.
     * - Her grup isteğin ardından rastgele bekleme süresi eklenir.
     *
     * @param int $numVisits Toplam ziyaret (istek) sayısı
     */
    public function simulateVisitors($numVisits = 10) {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        
        for ($i = 0; $i < $numVisits; $i++) {
            $ch = $this->createCurlHandle();
            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;

            // Eğer eşzamanlı istek limiti dolduysa veya son isteğe gelinmişse,
            // bu grup istekleri çalıştır.
            if ((count($curlHandles) % $this->numConcurrent) === 0 || $i === $numVisits - 1) {
                // Tüm isteklerin tamamlanmasını bekle
                do {
                    $status = curl_multi_exec($multiHandle, $active);
                    curl_multi_select($multiHandle);
                } while ($active && $status == CURLM_OK);

                // Her bir handle'ın çıktısını al (opsiyonel: loglama veya analiz için)
                foreach ($curlHandles as $ch) {
                    $response = curl_multi_getcontent($ch);
                    // Örneğin: yanıtları loglamak istersen:
                    // error_log("Response: " . substr($response, 0, 100));
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                }
                // Handle dizisini temizle
                $curlHandles = [];
                // Her grup istek sonrası rastgele bir bekleme süresi (1-5 saniye)
                sleep(rand(1, 5));
            }
        }
        curl_multi_close($multiHandle);
    }
}

// Kullanım Örneği
$targetUrl = "https://ornekwebsitesi.com"; // Ziyaretçi simülasyonu yapılacak site URL'si
$simulator = new VisitorSimulator($targetUrl, 5); // Aynı anda 5 istek gönderecek şekilde yapılandırıldı
$simulator->simulateVisitors(50); // Toplamda 50 ziyaret simülasyonu gerçekleştirir

?>
