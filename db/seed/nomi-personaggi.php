<?php

declare(strict_types=1);

/**
 * Bacini onomastici per area culturale, per generare i funzionari.
 *
 * Servono perché i personaggi siano **fittizi ma plausibili**: gli Stati sono
 * reali, le persone no, e un ministro della Difesa turco che si chiama Andersen
 * rompe l'illusione tanto quanto uno che si chiama come il ministro vero.
 *
 * Non è un elenco di nomi veri di nessuno: sono combinazioni di elementi
 * comuni, ricombinate dal generatore.
 */

return [
    'latino' => [
        'nomi'    => ['Marco', 'Elena', 'Luca', 'Chiara', 'Paolo', 'Giulia', 'Andrea', 'Sofia', 'Davide', 'Marta'],
        'cognomi' => ['Bertani', 'Colombo', 'Rossetti', 'Vitale', 'Marchetti', 'Guerrini', 'Lombardi', 'Fabbri', 'Serra', 'Parisi'],
    ],
    'iberico' => [
        'nomi'    => ['Ramón', 'Lucía', 'Álvaro', 'Pilar', 'Diego', 'Inés', 'Javier', 'Carmen', 'Tomás', 'Rosa'],
        'cognomi' => ['Mendoza', 'Salazar', 'Iglesias', 'Ferreira', 'Vargas', 'Pineda', 'Quintana', 'Arévalo', 'Bustos', 'Carvalho'],
    ],
    'germanico' => [
        'nomi'    => ['Klaus', 'Ingrid', 'Bernd', 'Anneke', 'Lars', 'Frieda', 'Joris', 'Maren', 'Stefan', 'Birgit'],
        'cognomi' => ['Steinmann', 'Vogelsang', 'Haverkamp', 'Lindqvist', 'Brandt', 'Wessels', 'Dahlberg', 'Krüger', 'Boonstra', 'Rasmussen'],
    ],
    'anglosassone' => [
        'nomi'    => ['Alan', 'Margaret', 'Douglas', 'Eileen', 'Grant', 'Nora', 'Wesley', 'Claire', 'Rowan', 'Beth'],
        'cognomi' => ['Hollingsworth', 'Marsden', 'Whitaker', 'Fairbanks', 'Thornley', 'Ashgrove', 'Denholm', 'Calloway', 'Redmayne', 'Sutcliffe'],
    ],
    'slavo' => [
        'nomi'    => ['Andrej', 'Milena', 'Bogdan', 'Katarina', 'Vadim', 'Ljuba', 'Tomasz', 'Zofia', 'Nikolaj', 'Dragana'],
        'cognomi' => ['Voronin', 'Jankovic', 'Kaminski', 'Belousov', 'Horvat', 'Marchenko', 'Zelinski', 'Popovic', 'Tarasov', 'Nowicki'],
    ],
    'arabo' => [
        'nomi'    => ['Karim', 'Nadia', 'Tarek', 'Samira', 'Rashid', 'Leila', 'Mounir', 'Yasmin', 'Fouad', 'Dalia'],
        'cognomi' => ['al-Rashidi', 'Haddad', 'ben Saleh', 'al-Mansouri', 'Khoury', 'Barakat', 'al-Fayed', 'Nasrallah', 'Sabbagh', 'al-Amiri'],
    ],
    'persiano' => [
        'nomi'    => ['Darius', 'Shirin', 'Kaveh', 'Nasrin', 'Farhad', 'Mitra', 'Bijan', 'Roya', 'Arash', 'Parisa'],
        'cognomi' => ['Nadervand', 'Esfahani', 'Behrouzi', 'Shirazi', 'Tabatabai', 'Ghaffari', 'Mirzaei', 'Kermani', 'Rostami', 'Daneshvar'],
    ],
    'turco' => [
        'nomi'    => ['Emre', 'Sibel', 'Kerem', 'Deniz', 'Volkan', 'Esra', 'Bülent', 'Nihan', 'Serkan', 'Ayla'],
        'cognomi' => ['Demirkan', 'Yıldırım', 'Karaosman', 'Erdoğdu', 'Şahinkaya', 'Aydoğan', 'Kocatürk', 'Balaban', 'Özdemir', 'Tekindağ'],
    ],
    'indiano' => [
        'nomi'    => ['Vikram', 'Ananya', 'Rohit', 'Meera', 'Arjun', 'Kavita', 'Sanjay', 'Priya', 'Nikhil', 'Shalini'],
        'cognomi' => ['Chandrasekhar', 'Bhattacharya', 'Rajagopal', 'Venkatesan', 'Deshpande', 'Kulkarni', 'Nambiar', 'Iyengar', 'Malhotra', 'Sengupta'],
    ],
    'sinitico' => [
        'nomi'    => ['Wei', 'Lin', 'Hao', 'Mei', 'Jian', 'Yun', 'Feng', 'Ning', 'Tao', 'Xia'],
        'cognomi' => ['Zhang', 'Liang', 'Huang', 'Shen', 'Cheng', 'Guo', 'Tan', 'Xu', 'Fang', 'Lu'],
    ],
    'giapponese' => [
        'nomi'    => ['Haruki', 'Ayumi', 'Kenji', 'Naoko', 'Satoshi', 'Rika', 'Daichi', 'Emi', 'Tatsuya', 'Yuki'],
        'cognomi' => ['Kurosawa', 'Hayashida', 'Muraoka', 'Shimizu', 'Arakawa', 'Onodera', 'Takagi', 'Nishimura', 'Fujimori', 'Sakaguchi'],
    ],
    'coreano' => [
        'nomi'    => ['Jinho', 'Soyeon', 'Minjae', 'Haeun', 'Doyun', 'Nari', 'Seungmin', 'Yerin', 'Taeho', 'Eunji'],
        'cognomi' => ['Kang', 'Ryu', 'Shin', 'Baek', 'Hwang', 'Jeon', 'Moon', 'Seo', 'Yun', 'Oh'],
    ],
    'sudestasiatico' => [
        'nomi'    => ['Bayu', 'Intan', 'Thanawat', 'Mai', 'Rizal', 'Siti', 'Dung', 'Arif', 'Nurul', 'Somchai'],
        'cognomi' => ['Wirawan', 'Sihombing', 'Rattanakul', 'Nguyen Van', 'Santoso', 'Hidayat', 'Chaiyaporn', 'Pangestu', 'Tanjung', 'Prasetyo'],
    ],
    'ebraico' => [
        'nomi'    => ['Avner', 'Tamar', 'Eitan', 'Noa', 'Yoav', 'Shira', 'Amit', 'Dafna', 'Gilad', 'Maya'],
        'cognomi' => ['Ben-Artzi', 'Shalev', 'Rosenfeld', 'Oren', 'Halevi', 'Barzilai', 'Gurevich', 'Almog', 'Feldman', 'Ziv'],
    ],
    'africano' => [
        'nomi'    => ['Kwame', 'Amina', 'Thabo', 'Ngozi', 'Sekou', 'Fatou', 'Obiageli', 'Musa', 'Zola', 'Adama'],
        'cognomi' => ['Okonkwo', 'Mwangi', 'Diallo', 'Nkemdirim', 'Sithole', 'Banda', 'Traoré', 'Adeyemi', 'Mensah', 'Kabila'],
    ],
];
