<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        #game td {
            width: 20px;
            height: 20px;
            table-layout: fixed;
            border: 1px solid black;
        }

        #game td.gamer1 {
            background: yellow;
        }
        #game td.gamer2 {
            background: green;
        }
        #game td.winner {
            border: 1px solid red;
        }

    </style>
    <title>5 tochek</title>
</head>
<body>
    <div id="game"></div>

<script type="text/javascript">

    class Field{
            constructor(selector, rowsNum, colsNum){
                this._gameEnd = false;

                this._field = document.querySelector(selector);
                this._colsNum = colsNum;
                this._rowsNum = rowsNum;

                this._dots = new Dots;
                this._html = new HTML;
                this._queue = new Queue(["gamer1", "gamer2"]);

                this._html.createTable(this._field, this._rowsNum, this._colsNum);
                this._run();
            }

            //Процесс игры. Нажатие, проверка точек.
            _run(){
                this._field.addEventListener("click", () => {
                    let cell = event.target.closest("td:not(.gamer)");

                    if(!this._gameEnd && cell){
                        let col = this._html.getPrevSiblingsNum(cell);
                        let row = this._html.getPrevSiblingsNum(cell.parentElement);

                        let gamer = this._queue.getGamer();
                        let dot = new Dot(gamer, cell, row, col, this._dots);
                        this._dots.add(dot, row, col);
                        console.log(dot);

                        //Добавили точку и проверили не выйграла ли она
                        let winLine = this._checkWin(dot);

                        //Если chekWin вернул не undefined, значит
                        //там нужное для победы кол-во точек.
                        if(winLine){
                            this._win(winLine);
                        }
                    }
                });
            }

            //Заканчиваем игру и
            _win(winLine){
                this._gameEnd = true;
                this._notifyWinnerCells(winLine);
            }

            //добавляем выйгрышным точкам класс "winner"
            _notifyWinnerCells(winLine){
                winLine.forEach((dot) => {
                    dot.becomeWinner();
                });
            }

            _checkWin(dot){
                let dirs = [
                    {deltaRow: 0, deltaCol: -1},
                    {deltaRow: -1, deltaCol: -1},
                    {deltaRow: -1, deltaCol: 0},
                    {deltaRow: -1, deltaCol: 1}
                ];

                //Проверили направления от точки и если в checkline содержит
                //5 или более точек, то возвращаем это значение.
                for(let i = 0; i < dirs.length; i++){
                    let line = this._checkLine(dot, dirs[i].deltaRow, dirs[i].deltaCol);

                    if(line.length >= 5){
                        return line;
                    }
                }
            }

            //Проверка противоположных от точки направлений и
            //добавление всех найденных точек в общий массив.
            _checkLine(dot, deltaRow, deltaCol){
                let dir1 = this._checkDir(dot, deltaRow, deltaCol);
                let dir2 = this._checkDir(dot, -deltaRow, -deltaCol);

                return [].concat(dir1, [dot], dir2);
            }

            //Проверка одного направления от точки.
            //Если у нее есть соседи, они добавятся в массив result
            _checkDir(dot, deltaRow, deltaCol){
                let result = [];
                let neighbor = dot;

                while(true){
                    neighbor = neighbor.getNeighbor(deltaRow, deltaCol);

                    if(neighbor){
                        result.push(neighbor);
                    }
                    else{
                        return result;
                    }
                }
            }
    }

    class Dot{
        constructor(gamer, elem, row, col, dots){
            this._gamer = gamer;
            this._elem = elem;
            this._row = row;
            this._col = col;
            this._dots = dots;

            this._neighbors = {};

            this._findNeighbors();
            this._notifyNeighbors();
            this._reflect();
        }

        getRow(){
            return this._row;
        }
        getCol(){
            return this._col;
        }
        becomeWinner(){
            this._elem.classList.add('winner');
        }

        //Получение соседей, если они есть
        getNeighbor(deltaRow, deltaCol){
            if(this._neighbors[deltaRow] !== undefined){
                return this._neighbors[deltaRow][deltaCol];
            }
            else{
                return undefined;
            }
        }

        //Если есть соседи
        addNeighbor(neighbor){
            let deltaRow = neighbor.getRow() - this._row;
            let deltaCol = neighbor.getCol() - this._col;

            if(this._neighbors[deltaRow] === undefined){
                this._neighbors[deltaRow] = {};
            }

            this._neighbors[deltaRow][deltaCol] = neighbor;
        }

        //Где ищем соседей, относительно точки
        _findNeighbors(){
            this._considerNeighbor(1,1);
            this._considerNeighbor(1,0);
            this._considerNeighbor(1,-1);
            this._considerNeighbor(-1,1);
            this._considerNeighbor(-1,0);
            this._considerNeighbor(-1,-1);
            this._considerNeighbor(0,1);
            this._considerNeighbor(0,-1);
        }


        _considerNeighbor(deltaRow, deltaCol){
            let neighbor = this._dots.get(this._row + deltaRow, this._col + deltaCol);

            if(neighbor !== undefined && neighbor._belongsTo(this._gamer)){
                this.addNeighbor(neighbor);
            }
        }

        //Оповещение ранее поставленных точек о появлении новой
        _notifyNeighbors(){
            for(let rowKey in this._neighbors){
                for(let colKey in this._neighbors[rowKey]){
                    this._neighbors[rowKey][colKey].addNeighbor(this);
                }
            }
        }

        //Добавление точке классов.
        _reflect(){
            this._elem.classList.add('gamer');
            this._elem.classList.add(this._gamer);
        }

        //Чья точка
        _belongsTo(gamer){
            return this._gamer == gamer;
        }

    }

    //Чья очередь ходить
    class Queue{
        constructor(gamers){
            this._gamers = gamers;
            this._counter = new Counter(this._gamers.length);
        }

        getGamer(){
            return this._gamers[this._counter.get()];
        }
    }

    //Счетчик очереди
    class Counter{
        constructor(length){
            this._length = length;
            this._counter = null;
        }

        get(){
            if(this._counter == null){
                this._counter = 0;
            }
            else{
                this._counter++;

                if(this._counter == this._length){
                    this._counter = 0;
                }
            }

            return this._counter;
        }
    }

    class Dots{
        constructor(){
            this._dots = {};
        }

        add(dot, row, col){
            if(this._dots[row] === undefined){
                this._dots[row] = {};
            }

            this._dots[row][col] = dot;
        }

        get(row, col){
            if(this._dots[row] && this._dots[row][col]){
                return this._dots[row][col];
            }
            else{
                return undefined;
            }
        }
    }


    class HTML{
        createTable(parent, rowsNum, colsNum){
            let table = document.createElement('table');

            for(let i = 0; i < rowsNum; i++){
                let tr = document.createElement('tr');

                for(let j = 0; j < colsNum; j++){
                    let td = document.createElement('td');
                    tr.appendChild(td);
                }

                table.appendChild(tr);
            }

            parent.appendChild(table);
        }

        //Для нахождения координаты ячейки.
        //Elem - tr или td
        getPrevSiblingsNum(elem){
            let prev = elem.previousSibling;
            let i = 0;

            while(prev){
                prev = prev.previousSibling;
                i++;
            }

            return i;
        }
    }

    //Точка входа
    new Field("#game", 20, 20);

    </script>
</body>
</html>
