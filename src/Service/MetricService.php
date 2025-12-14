<?php
declare(strict_types=1);

namespace App\Service;

class MetricService
{
    private const METRIC_LABEL_UNAVAILABLE = 'Indisponivel';
    private const METRIC_DESC_UNAVAILABLE = 'Sem leitura recente do sensor';

    public function classifyAirQuality(?float $gasKiloOhms): array
    {
        $classification = [
            'label' => self::METRIC_LABEL_UNAVAILABLE,
            'tone' => 'gray',
            'description' => 'Aguardando leitura do sensor',
        ];

        if ($gasKiloOhms === null || !is_finite($gasKiloOhms) || $gasKiloOhms <= 0) {
            return $classification;
        }

        if ($gasKiloOhms >= 300) {
            return ['label' => 'Excelente', 'tone' => 'emerald', 'description' => 'Ar muito limpo e estavel'];
        }
        if ($gasKiloOhms >= 200) {
            return ['label' => 'Boa', 'tone' => 'sky', 'description' => 'Qualidade adequada para uso geral'];
        }
        if ($gasKiloOhms >= 120) {
            return ['label' => 'Moderada', 'tone' => 'amber', 'description' => 'Pequeno aumento de VOCs detectado'];
        }
        if ($gasKiloOhms >= 60) {
            return ['label' => 'Atencao', 'tone' => 'orange', 'description' => 'Elevacao significativa de VOCs'];
        }
        return ['label' => 'Critica', 'tone' => 'red', 'description' => 'Alta concentracao de VOCs, investigar fontes'];
    }

    public function classifyTemperature(?float $temperatureCelsius): array
    {
        $classification = [
            'label' => self::METRIC_LABEL_UNAVAILABLE,
            'tone' => 'gray',
            'description' => self::METRIC_DESC_UNAVAILABLE,
        ];

        if ($temperatureCelsius === null || !is_finite($temperatureCelsius)) {
            return $classification;
        }

        if ($temperatureCelsius >= 35) {
            return ['label' => 'Muito quente', 'tone' => 'red', 'description' => 'Risco de desconforto termico elevado'];
        }
        if ($temperatureCelsius >= 30) {
            return ['label' => 'Quente', 'tone' => 'orange', 'description' => 'Temperatura elevada, mantenha-se hidratado'];
        }
        if ($temperatureCelsius >= 22) {
            return ['label' => 'Agradavel', 'tone' => 'emerald', 'description' => 'Conforto termico para a maioria das pessoas'];
        }
        if ($temperatureCelsius >= 16) {
            return ['label' => 'Ameno', 'tone' => 'sky', 'description' => 'Clima moderado, considere agasalho leve'];
        }
        if ($temperatureCelsius >= 5) {
            return ['label' => 'Frio', 'tone' => 'amber', 'description' => 'Sensacao de frio, utilize agasalho'];
        }
        return ['label' => 'Muito frio', 'tone' => 'red', 'description' => 'Temperatura muito baixa, proteja-se do frio'];
    }

    public function classifyHumidity(?float $humidityPercent): array
    {
        $classification = [
            'label' => self::METRIC_LABEL_UNAVAILABLE,
            'tone' => 'gray',
            'description' => self::METRIC_DESC_UNAVAILABLE,
        ];

        if ($humidityPercent === null || !is_finite($humidityPercent)) {
            return $classification;
        }

        if ($humidityPercent >= 85) {
            return ['label' => 'Muito alta', 'tone' => 'red', 'description' => 'Sensacao abafada e risco de mofo'];
        }
        if ($humidityPercent >= 70) {
            return ['label' => 'Alta', 'tone' => 'orange', 'description' => 'Pode gerar desconforto ou condensacao'];
        }
        if ($humidityPercent >= 45) {
            return ['label' => 'Confortavel', 'tone' => 'emerald', 'description' => 'Faixa adequada para a maioria das pessoas'];
        }
        if ($humidityPercent >= 30) {
            return ['label' => 'Baixa', 'tone' => 'amber', 'description' => 'Ar seco pode causar desconforto'];
        }
        return ['label' => 'Muito baixa', 'tone' => 'red', 'description' => 'Risco de ressecamento das vias aereas'];
    }

    public function classifyUv(?float $uvIndex): array
    {
        $classification = [
            'label' => self::METRIC_LABEL_UNAVAILABLE,
            'tone' => 'gray',
            'description' => self::METRIC_DESC_UNAVAILABLE,
        ];

        if ($uvIndex === null || !is_finite($uvIndex) || $uvIndex < 0) {
            return $classification;
        }

        if ($uvIndex >= 11) {
            return ['label' => 'Extrema', 'tone' => 'red', 'description' => 'Protecao maxima necessaria (UV 11+)'];
        }
        if ($uvIndex >= 8) {
            return ['label' => 'Muito alta', 'tone' => 'orange', 'description' => 'Minimize exposicao direta ao sol'];
        }
        if ($uvIndex >= 6) {
            return ['label' => 'Alta', 'tone' => 'amber', 'description' => 'Use protecao solar e chapeu'];
        }
        if ($uvIndex >= 3) {
            return ['label' => 'Moderada', 'tone' => 'sky', 'description' => 'Protecao recomendada nos horarios criticos'];
        }
        return ['label' => 'Baixa', 'tone' => 'emerald', 'description' => 'Exposicao ao sol com baixo risco'];
    }
}

