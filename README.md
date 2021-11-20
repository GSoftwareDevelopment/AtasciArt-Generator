# High Score Cafe Atasci Generator

## Krótko, czym jest HSC

High Score Cafe (HSC) jest usługą udostępnioną przez Krzysztofa XXL Dudka, która gromadzi i prezentuje listy wyników użytkowników z gier, przeznaczonych na 8-bitowe komputery ATARI.

Przesyłanie wyników odbywa się na trzy różne sposoby:

- ręcznie dodanie za pośrednictwem serwisu HSC
- kod QR generowany w grze na małym Atari
- API HSC, korzystające z urządzenia FujiNet.

Więcej na temat serwisu pod linkiem [High Score Cafe](https://xxl.atari.pl/hsc/)

## Czym jest HSC Atasci Generator?

Jest to skrypt napisany w języku PHP, pozwalający generować ekrany dla komputera ATARI z listą wyników danej gry oraz grafiką AtasciiArt.
Ekran jest generowany (po stronie serwera) i w postaci czytelnej dla małego ATARI, przesyłany jest do interfaceu FujiNet za pośrednictwem internetu. Po odebraniu przez komputer danych, ekran może być wpisany bezpośrednio do pamięci ekranu komputera Atari, bez konieczności przetwarzania informacji.

Atutem takiego rozwiązania są:

- udekorowanie wyników grafiką AtasciiArt
- brak konieczności przetwarzania danych JSON po stronie Atari
- szybki dostęp do listy wyników wielu gier.

## Co to jest Plik konfiguracyjny?

Jest to plik w formacie JSON. Opisuje on właściwości i elementy generowanego ekranu AtasciiArt.

    Ważne, aby pamiętać, że wielkość liter w nazwach atrybutów oraz ich wartościach MA ZNACZENIE!

### Sekcja `layouts`

Z punktu widzenia pliku JSON, `layouts` jest objektem w którym umieszczone są definicje wyglądu ekranów. Każda taka definicja to osobny object.

```JSON
{
 "layouts":{
  "default":{
   ...
  },
  "layout_1":{
   ...
  },
  "layout_1":{
   ...
  }
 }
}
```

Powyższy przykład, przedstawia definicję trzech ekranów:

- `default` jest zarezerwowana dla domyślnego wyglądu
- `layout_1` i `layout_2` są dodatkowymi ekreanami

#### Definicwanie wyglądu ekranu

__Atrybuty wymagane:__

- `width`, `height` - szerokość i wysokość całkowita w znakach.
- `elements` - tablica obiektów opisująca elementy generowane przez skrypt.

__Opcjonalne atrybuty:__

- `colors` - tablica reprezentująca ustawienia kolorów (wartości dla rejestrów od 708 do 712)
- `encodeAs` - sposób wyjściowego kodowania treści elementów
- `screenData` - tablica ciągów tekstowych opisująca zawartość ekranu bazowego (dane heksadecymalne)
- `screenFill` - znak, jakim będzie wypełniony ekran bazowy.

## Sekcja `elements`

Jest to tablica objektów (w rozumieniu pliku JSON). Każdy objekt w tej sekcji, definiuje osobną linię w ekranie bazowym.

__Atrybuty wymagane:__

- `x` i `y` - określające początkowe położenie elementu w ekranie bazowym
- `width` - szerokość elementu

__Opcjonalne atrybuty:__

- `inversLine`, ustawiony na `true`, dokonuje inwersji (operacja XOR na 7 bicie każdego znaku)  w wynikowym elemencie

W objecie sekcji, definiowane są też elementy.

### Typy elementów

Typ generowanego elementu zawarty jest w nazwie atrybutu, objektu opisującego generowaną linię tablicy `elements`

```JSON
{
 "layouts":{
  "default":{
   "elements":[
    {
     "x": 1,
     "y": 1,
     "width": 20,
     "element_type": {
      {element_attributes}
     },
     "element_type": {
      {element_attributes}
     },...
    },...
   ]
  }
 }
}
```

### Rodzaje elementów

- `place` - miejsce z tablicy wyników
- `nick` - nazwę gracza (jego nick)
- `score` - osiągnięty wynik
- `date` - datę rejestracji wyniku
- `text` - generuje dowolny tekst
- `genTime` - generuje czas powstania ekranu

Każdy element może posiadać etykietę. Jej nazwę definiujemy zaraz po typie elementu, poprzedzając ją znakiem kropki.

```JSON
{
 "text.label":{...}
}
```

Jest ona wymagana w przypadku chęci wstawienia kilku elementów tego samego typu. Główne ma zastosowanie w eleencie typu `text`.

### Atrybuty opisujące element

__Atrybuty wymagane:__

- `shift` - przesunięcie względem początku linii (w znakach)
- `width` - szerokość generowanej wartości (w znakach)

__Opcjonalnie atrybuty:__

- `align` - justowanie zawartości względem podanej szerokości objektu (atrybut `width`) Możliwe wartości to: `left`, `center`, `right`. Wartość `right` jest domyślna.
- `fillChar` - znak, jakim będzie wypełniony objekt na całej jego szerokości. Domyślną wartością jest znak #32 (spacja)
- `letterCase` - pozwala na kowersję wielkości liter. Możliwe wartości: `uppercase`,`lowercase`
- `limitChars` - zawiera zestaw znaków, jaki jest akceptowany przy wyświetlaniu. Jego opis to wartość typu string, zawierająca wszystkie chciane znaki. W parze z tym atrybutem jest atrybut `replaceOutsideChars`. Domyślnie akceptowane są wszystkie znaki.
- `replaceOutsideChars` - ten atrybut określa znak, jaki będzie wstawiany w przypadku, gdy znak objektu nie należy do zakresu określnego w atrybucie `limitChars`. Domyślną wartością jest #32 (spacja)
- `invert` - działa tak samo jak atrybut `inversLine` w sekcji `scoreList` z tą różnicą, że stosowany jest tylko do generowanego objektu.

### Dedykowane atrybuty elementów

Spośród wszystkich elementów można wybrać takie, które mają przypisane dodatkowe atrybuty. Takimi elementami są:

- `score`
- `date`
- `genTime`
- `text`

#### Atrybuty elementu `score`

Element wyniku `score` domyślnie interpretowana jest jako wartość 32-bitowa typu całkowitego, przedstawiająca wynik punktowy osiągnięty przez gracza. Może być też przedstawiona jako czas.

Czas zapisywany jest w postaci liczby całkowitej zawierającej część ułamkową, której dokładność określa atrybut `precision` w zakresie od 2 do 100. Wartośc `precision` należy rozumieć jako część sekundy 1/n. Najlepiej będzie to zrozumieć, przedstawiając to w tabeli:

| `score` | `precision` | rezultat |
| ------- | ----------- | -------- |
| 1       | 5 (1/5s)    | 00s.20   |
| 5       |             | 01s.00   |
| 51      |             | 10s.20   |
| 1       | 50 (1/50s)  | 00s.02   |
| 5       |             | 00s.10   |
| 55      |             | 01s.10   |

Aby przekształcić wynik do formatu czasu, należy zdefiniować następujące atrybuty w elemencie `score`:

```JSON
{
 "showScoreAs": "time",
 "precision": 50,
 "format": "h.m.f"
}
```

- `showScoreAs` - wartość tego atrybutu określ jako `time`
- `precision` - określ dokładność z jaką będzie interpretowana wartość wyniku (1/n części sekundy)
- `format` - opisz format, który będzie zastosowany w wyniku.

`format` jest ciągiem znaków, który opisuje jakie części czasu będą wyświetlane. Znaczenie znaków w tym ciągu jest następująca:

- `h` - ilość godzin (bez zera wiodącego)
- `Hn` - ilość godzin, gdzie `n` określa ilość zer wiodących (jedna cyfra)
- `m` - ilość minut (z zerem wiodącym)
- `s` - ilość sekund (z zerem wiodącym)
- `f` - część ułamkowa sekundy (dwie cyfry)
- `Fn` - j.w. tylko n określa ilość miejsc po przecinku.

Nierozpoznane znaki w ciągu formatu zostaną przedstawione bez zmian.

#### Atrybuty elementu `date`

Atrybutem rozszerzającym element `date` jest `format`. Jest to ciąg znaków opisujących sposób, w jaki ma być interpretowana data powstania wyniku. Domyślnie stosowany jest format `Y.m.d`

Funkcją formatującą czas jest funkcja języka PHP `date()`. Jej opis znajdziesz [tu](https://www.php.net/manual/en/function.date.php), a możliwe opcje formatowania [tu](https://www.php.net/manual/en/datetime.format.php).

#### Atrybuty elemenu `genTime`

Patrz opis atrybutów elementu `date`

#### Atrybuty elementu `text`

Użyj atrybutu `content` celem, określenia treści generowanego tekstu.

## Sekcja `elementScheme` - Schematy definicji elementów

Aby ułatwić projektowanie schematu oraz zwiększyć czytelność pliku konfiguracyjnego, można stosować **schematy definicji elementów**.

Ich definicje opisuje się w głównej częśći pliku konfiguracyjnego w sekcji `elementSchemes` i jest ona objectem (JSON) w którym zawarte są poszczególne schematy.

Każdy schemat jest obiektem (JSON) i musi być nazwany, np:

```JSON
{
  ...
  "elementSchemes": [
    "my_schema": {
      ...
    }
  ],
  ...
}
```

W definicji schematu można stosować wszystkie elementy i ich atrybuty, które zostały wymienione w [Sekcja `elements`](#sekcja-elements).

Użycie schematu jest banalnie proste. W definicji linii wyniku wstawiamy atrybut `useSchema` któremu przypisujemy nazwę zdefiniowanego schematu (wielkość liter ma znaczenie!)

```JSON
{
 ...
 "schemes": [
  "my_schema": {
   "x": 5,
   "width": 20,
   "place": {
    "shift": 1,
    "width": 2
    "align": right
   },
   ...
   "invertLine": false
  }
 ],
 "layouts": {
  "default":{
   ...
   "elements":[
    {
     "y": 5,
     "useSchema": "my_schema",
     "invertLine": true
    },
    {
     "y": 7,
     "useSchema": "my_schema"
    }
    ...
   ]
  }
  ]
}
```

Obiekty i atrybuty zdefiniowane w linii wyniku, mają priorytet nad schematem, dzięki czemu, można nadpisywać ustawiane przez schemat cechy.

Już prościej się (chyba) nie da ;) Znaczy się, da, tylko to już jest przerost formy nad treścią.
