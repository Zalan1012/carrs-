<?php
// Szabályalapú chatbot: az eladó nevében válaszol az érdeklődő üzenetére.
// Kulcsszavakat keres az üzenetben, és az adott autó adataiból állítja össze a választ.
// Nem mesterséges intelligencia – új témát a $rules tömb bővítésével lehet hozzáadni.

/** Kisbetűsít és leveszi az ékezeteket, hogy az "ár", "Ár", "ar" ugyanaz legyen. */
function bot_normalize(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    return strtr($text, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
    ]);
}

/**
 * A chatbot válasza.
 *   $car     – az autó sora a cars táblából
 *   $text    – az érdeklődő üzenete
 *   $earlier – hány üzenetet írt már korábban ebben a beszélgetésben (a változatos válaszokhoz)
 */
function bot_reply(array $car, string $text, int $earlier): string
{
    $t     = bot_normalize($text);
    $title = $car['brand'] . ' ' . $car['model'];
    $price = (int) $car['price'];
    $power = (int) $car['power'];
    $fuel  = $car['fuel'];

    // Alkunál kb. 3%-ot enged, tízezerre kerekítve
    $deal = (int) (floor($price * 0.97 / 10000) * 10000);

    // Fogyasztás: becslés az üzemanyag és a teljesítmény alapján
    if ($fuel === 'Elektromos') {
        $consumption = 'Átlagosan kb. ' . (17 + (int) round($power / 100)) . ' kWh/100 km-t fogyaszt.';
    } elseif ($fuel === 'Hibrid') {
        $consumption = 'Vegyesben kb. ' . number_format(3.5 + $power / 150, 1, ',', '') . ' l/100 km, rövid távon tisztán elektromosan is megy.';
    } elseif ($fuel === 'Dízel') {
        $consumption = 'Vegyesben kb. ' . number_format(4.5 + $power / 90, 1, ',', '') . ' l/100 km a fogyasztása.';
    } else {
        $consumption = 'Vegyesben kb. ' . number_format(6 + $power / 70, 1, ',', '') . ' l/100 km a fogyasztása.';
    }

    $description = trim((string) $car['description']);

    // [ minta (ékezet nélküli szövegre), válasz ] – a sorrend számít, legfeljebb 3 találatot fűzünk össze
    $rules = [
        ['/\b(alku|alkudh|olcsobb|enged|utolso ar|lejjebb|kedvezmeny)/',
            'Kis alku belefér: komoly érdeklődőnek ' . format_price($deal) . '-ért odaadom.'],
        ['/\b(km|kilometer|futas|futott|mennyit ment)/',
            format_km($car['mileage']) . ' van benne, a kilométeróra-állás igazolt.'],
        ['/\b(ar|ara|arat|arban|iranyar|mennyi(be|ert)|mennyi az|forint|ft)\b/',
            'Az irányár ' . format_price($price) . '.'],
        ['/\b(evjarat|eves|mikori|hanyas|melyik ev)/',
            $car['year'] . '-es évjárat.'],
        ['/\b(fogyaszt|hatotav)/',
            $consumption],
        ['/\b(uzemanyag|benzin|dizel|hibrid|elektromos|villany|tolt)/',
            'Üzemanyag: ' . mb_strtolower($fuel) . '.'],
        ['/\b(valto|automata|manualis|kezi valto)/',
            mb_strtolower($car['transmission']) === 'automata' ? 'Automata váltós.' : 'Manuális váltós.'],
        ['/\b(loer|teljesitmeny|eros\b|kw\b|hany le\b)/',
            $power . ' LE (' . (int) round($power * 0.7355) . ' kW) a teljesítménye.'],
        ['/\b(karosszeria|szedan|kombi|kupe|ajtos)/',
            'Karosszéria: ' . mb_strtolower($car['body_type']) . '.'],
        ['/\b(allapot|serul|tores|tort|rozsda|hiba|szerviz|muszaki|karc)/',
            $description !== '' ? 'Az állapotáról: ' . mb_strimwidth($description, 0, 220, '…') : 'Korának megfelelő, megkímélt állapotban van, sérülésmentes.'],
        ['/\b(megnez|nezni|neznem|megtekint|talalkoz|proba|kiprobal|idopont|mikor er|raer|hetvege|holnap)/',
            'Persze, meg lehet nézni és ki is lehet próbálni. Hétköznap délután vagy hétvégén érek rá, neked mikor lenne jó?'],
        ['/\b(csere|beszamit)/',
            'Cserét és beszámítást sajnos nem tudok vállalni.'],
        ['/\b(hol |merre|varos|cim|helyszin)/',
            'A pontos helyszínt megbeszéljük, ha megvan az időpont.'],
        ['/\b(telefon|szamod|hivhat|felhiv)/',
            'Egyelőre itt, üzenetben egyeztessünk, utána megadom a számom.'],
        ['/\b(elerheto|elado|megvan|aktualis|meg van)/',
            'Igen, a ' . $title . ' még eladó.'],
    ];

    $answers = [];
    foreach ($rules as [$pattern, $answer]) {
        if (preg_match($pattern, $t) && count($answers) < 3) {
            $answers[] = $answer;
        }
    }

    $greeting = (bool) preg_match('/\b(szia|sziasztok|hello|hali|helo|udv|jo napot|jo reggelt|jo estet)/', $t);
    $thanks   = (bool) preg_match('/\b(kosz|koszi|koszonom|kossz)/', $t);

    if ($answers) {
        return ($greeting ? 'Szia! ' : '') . implode(' ', $answers);
    }
    if ($thanks) {
        return 'Szívesen! Ha van még kérdésed, írj nyugodtan.';
    }
    if ($greeting) {
        return 'Szia! Igen, a ' . $title . ' még eladó. Miben segíthetek?';
    }

    // Ha egyik kulcsszó sem illett: általános válaszok váltakozva
    $fallbacks = [
        'Köszi az üzenetet! A ' . $title . ' még eladó. Kérdezhetsz az áráról, a futott km-ről, az állapotáról, vagy egyeztethetünk megtekintést.',
        'Ezt most nem tudom pontosan megmondani, de személyesen mindent megmutatok. Mikor tudnád megnézni?',
        'Jó kérdés. Írd meg, mi érdekel pontosan: ár, km, évjárat, fogyasztás vagy állapot?',
    ];
    return $fallbacks[$earlier % count($fallbacks)];
}
