<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://unpkg.com/konva@8.3.5/konva.min.js"></script>
    <style type="text/css">
        @page { margin: 0; }
        #container, #back{
            background-image: url({{ "/storage/" .$certificado->caminho }});
            background-repeat: no-repeat;
            background-size: 100% 100%;
            width: 1118px;
            height: 790px;
        }

        .d-none {
            display: none;
        }
    </style>

</head>
    <body style="text-align: center;">
        <div id="container"></div>
        <div id="back" style="margin-top: 10px"></div>
        <button onclick="send()">Salvar</button>
        <form id="form" action="{{route('coord.cadastrarmedida')}}" class="d-none" method="POST">
            @csrf
            <input type="text" name="certificado_id" value="{{$certificado->id}}">
            @foreach ($certificado->assinaturas as $assinatura)
                @foreach (['nome', 'imagem', 'cargo'] as $objeto)
                    @foreach (['x', 'y', 'largura', 'altura', 'fontSize'] as $medida)
                        <input type="text" name="{{$objeto}}-{{$medida}}-{{$assinatura->id}}" value="0">
                    @endforeach
                @endforeach
            @endforeach
            @foreach (['texto', 'data', 'hash'] as $objeto)
                @foreach (['largura', 'x', 'y', 'fontSize'] as $medida)
                    <input type="text" name="{{$objeto}}-{{$medida}}" value="0">
                @endforeach
            @endforeach
            <input type="text" name="qrcode-x" value="0">
            <input type="text" name="qrcode-y" value="0">
            <input type="text" name="qrcode-largura" value="0">
            <input type="text" name="qrcode-altura" value="0">
        </form>
        <script>
            var stage = new Konva.Stage({
                container: 'container',
                width: 1118,
                height: 790,
            });

            var layer = new Konva.Layer();
            stage.add(layer);
            var medidas = {!! json_encode($medidas) !!}
            medida = medidas.find(m => m.tipo == 1);
            if(medida === undefined)
                medida = {x: 50, y: 350, largura: 1000, fontSize: 14}
            var imagemTransformer = new Konva.Transformer({
                keepRatio: true,
                enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
            });
            layer.add(imagemTransformer);

            var texto = new Konva.Text({
                x: parseInt(medida.x),
                y: parseInt(medida.y),
                width: parseInt(medida.largura),
                fontSize: medida.fontSize,
                text: '{!! json_encode(strip_tags($certificado->texto)) !!}',
                draggable: true,
                id: 'texto',
                name: 'texto',
            });

            texto.on('transform click tap move', (event) => {
                console.log(stage.find('#texto')[0].attrs);
            });

            medida = medidas.find(m => m.tipo == 2);
            if(medida === undefined)
                medida = {x: 915, y: 350, largura: 450, fontSize: 14}

            var localdata = {!! json_encode($certificado->local) !!} + ', ' + {!! json_encode($dataHoje) !!};
            var local = new Konva.Text({
                x: parseInt(medida.x),
                y: parseInt(medida.y),
                width: parseInt(medida.largura),
                fontSize: parseInt(medida.fontSize),
                text: localdata.replace(/[\n\r]/g,' ').replace('"', '').replace('"', ''),
                draggable: true,
                name: 'texto',
                id: 'data',
            });
            layer.add(local);

            var MIN_WIDTH = 100;
            var textoTransformer = new Konva.Transformer({
                padding: 5,
                rotateEnabled: false,
                keepRatio: true,
                enabledAnchors: ['top-left', 'top-right', 'middle-right', 'middle-left', 'bottom-left', 'bottom-right'],
                // enable only side anchors
                // limit transformer size
                boundBoxFunc: (oldBox, newBox) => {
                if (newBox.width < MIN_WIDTH) {
                    return oldBox;
                }
                return newBox;
                },
            });
            layer.add(textoTransformer);

            texto.on('transform', (event) => {
                // with enabled anchors we can only change scaleX
                // so we don't need to reset height
                // just width
                if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(textoTransformer.getActiveAnchor()) ) {
                texto.setAttrs({
                    fontSize: Math.max(texto.fontSize() * texto.scaleX(), 2),
                    width: Math.max(texto.width() * texto.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                } else if ( ['middle-right', 'middle-left'].includes(textoTransformer.getActiveAnchor()) ) {
                texto.setAttrs({
                    width: Math.max(texto.width() * texto.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                }
            });
            layer.add(texto);

            local.on('transform', (event) => {
                // with enabled anchors we can only change scaleX
                // so we don't need to reset height
                // just width
                if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(textoTransformer.getActiveAnchor()) ) {
                local.setAttrs({
                    fontSize: Math.max(local.fontSize() * local.scaleX(), 2),
                    width: Math.max(local.width() * local.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                } else if ( ['middle-right', 'middle-left'].includes(textoTransformer.getActiveAnchor()) ) {
                local.setAttrs({
                    width: Math.max(local.width() * local.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                }
            });

            var assinaturas = {!! json_encode($certificado->assinaturas) !!};
            if (assinaturas.length > 1) {
                posicao_inicial_x = ((1268 - 100) / assinaturas.length) / assinaturas.length;
            } else {
                posicao_inicial_x = 1268 / 2 - 100;
            }
            var i = 0;
            assinaturas.forEach((assinatura, index) => {
                var assinaturaArray = [];
                var imageObj = new Image();
                imageObj.onload = function () {
                    medida = medidas.find(m => m.tipo == 5 && m.assinatura.id == assinatura.id);
                    // add the shape to the layer
                    if(medida === undefined) {
                        yoda = new Konva.Image({
                            x: posicao_inicial_x + (index * 350),
                            y: 600,
                            image: imageObj,
                            draggable: true,
                            id: 'imagem' + assinatura.id,
                            scaleX: 0.2,
                            scaleY: 0.2,
                        });
                        yoda.setAttrs({
                            height: yoda.height() * yoda.scaleY(),
                            width: yoda.width() * yoda.scaleX(),
                            scaleX: 1,
                            scaleY: 1,
                        });
                    } else {
                        medida = medidas.find(m => m.tipo == 5 && m.assinatura.id == assinatura.id);
                        yoda = new Konva.Image({
                            x: parseInt(medida.x),
                            y: parseInt(medida.y),
                            image: imageObj,
                            draggable: true,
                            id: 'imagem' + assinatura.id,
                            height: parseInt(medida.altura),
                            width: parseInt(medida.largura),
                        });
                    }
                    console.log(yoda.attrs)
                    layer.add(yoda);
                    yoda.on('transform', (event) => {
                        // with enabled anchors we can only change scaleX
                        // so we don't need to reset height
                        // just width
                        if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(imagemTransformer.getActiveAnchor()) ) {
                            event.target.setAttrs({
                                height: event.target.height() * event.target.scaleY(),
                                width: event.target.width() * event.target.scaleX(),
                                scaleX: 1,
                                scaleY: 1,
                            });
                        } else if ( ['middle-right', 'middle-left'].includes(imagemTransformer.getActiveAnchor()) ) {
                            event.target.setAttrs({
                                width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                                scaleX: 1,
                                scaleY: 1,
                            });
                        }
                    });
                };
                imageObj.src = '/storage/' + assinatura.caminho;

                var redLine = new Konva.Line({
                    points: [posicao_inicial_x + (index * 350), 550 + 106, posicao_inicial_x + (index * 350) + 256, 550 + 106],
                    stroke: 'grey',
                    strokeWidth: 3,
                    draggable: true,
                    id: 'linha' + assinatura.id,
                });

                layer.add(redLine);
                medida = medidas.find(m => m.tipo == 4 && m.assinatura.id == assinatura.id);
                var simpleText;
                if(medida === undefined) {
                    medida = {x: 915, y: 350, largura: 450, fontSize: 14}
                    simpleText = new Konva.Text({
                        x: posicao_inicial_x + (index * 350) + redLine.width() / 2,
                        y: 682,
                        text: assinatura.cargo,
                        fontSize: 12,
                        fontFamily: 'Arial, Helvetica, sans-serif',
                        draggable: true,
                        id: 'cargo' + assinatura.id,
                        name: 'texto',
                    });
                    simpleText.setAttrs({
                        x: simpleText.x() - simpleText.width() / 2,
                        width: simpleText.width() * simpleText.scaleX(),
                        scaleX: 1,
                        scaleY: 1,
                    });
                } else {
                    simpleText = new Konva.Text({
                        x: parseInt(medida.x),
                        y: parseInt(medida.y),
                        text: assinatura.cargo,
                        width: parseInt(medida.largura),
                        fontSize: parseInt(medida.fontSize),
                        fontFamily: 'Arial, Helvetica, sans-serif',
                        draggable: true,
                        id: 'cargo' + assinatura.id,
                        name: 'texto',
                    });
                }

                simpleText.on('transform', (event) => {
                    // with enabled anchors we can only change scaleX
                    // so we don't need to reset height
                    // just width
                    if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(textoTransformer.getActiveAnchor()) ) {
                        event.target.setAttrs({
                            fontSize: Math.max(event.target.fontSize() * event.target.scaleX(), 2),
                            width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                            scaleX: 1,
                            scaleY: 1,
                        });
                    } else if ( ['middle-right', 'middle-left'].includes(textoTransformer.getActiveAnchor()) ) {
                            event.target.setAttrs({
                            width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                            scaleX: 1,
                            scaleY: 1,
                        });
                    }
                });

                layer.add(simpleText);
                var simpleText;
                medida = medidas.find(m => m.tipo == 3 && m.assinatura.id == assinatura.id);
                if(medida === undefined) {
                    simpleText = new Konva.Text({
                        x: posicao_inicial_x + (index * 350) + redLine.width() / 2,
                        y: 666,
                        text: assinatura.nome,
                        fontSize: 12,
                        fontFamily: 'Arial, Helvetica, sans-serif',
                        draggable: true,
                        id: 'nome' + assinatura.id,
                        name: 'texto',
                    });
                    simpleText.setAttrs({
                        x: simpleText.x() - simpleText.width() / 2,
                        width: simpleText.width() * simpleText.scaleX(),
                        scaleX: 1,
                        scaleY: 1,
                    });
                } else {
                    simpleText = new Konva.Text({
                        x: parseInt(medida.x),
                        y: parseInt(medida.y),
                        text: assinatura.nome,
                        width: parseInt(medida.largura),
                        fontSize: parseInt(medida.fontSize),
                        fontFamily: 'Arial, Helvetica, sans-serif',
                        draggable: true,
                        id: 'nome' + assinatura.id,
                        name: 'texto',
                    });
                }

                simpleText.on('transform', (event) => {
                    // with enabled anchors we can only change scaleX
                    // so we don't need to reset height
                    // just width
                    if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(textoTransformer.getActiveAnchor()) ) {
                    event.target.setAttrs({
                        fontSize: Math.max(event.target.fontSize() * event.target.scaleX(), 2),
                        width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                        scaleX: 1,
                        scaleY: 1,
                    });
                    } else if ( ['middle-right', 'middle-left'].includes(textoTransformer.getActiveAnchor()) ) {
                    event.target.setAttrs({
                        width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                        scaleX: 1,
                        scaleY: 1,
                    });
                    }
                });

                layer.add(simpleText);
            });

            stage.on('mouseover', function () {
                document.body.style.cursor = 'pointer';
            });

            stage.on('mouseover', function () {
                document.body.style.cursor = 'default';
            });

            // clicks should select/deselect shapes
            stage.on('click tap', function (e) {

                // if click on empty area - remove all selections
                if (e.target === stage) {
                    imagemTransformer.nodes([]);
                    textoTransformer.nodes([]);
                    return;
                }

                // do we pressed shift or ctrl?
                const metaPressed = e.evt.shiftKey || e.evt.ctrlKey || e.evt.metaKey;
                const isSelected = imagemTransformer.nodes().indexOf(e.target) >= 0;

                if (!metaPressed && !isSelected) {
                    // if no key pressed and the node is not selected
                    // select just one
                    if(stage.find('.texto').includes(e.target)) {
                        textoTransformer.nodes([e.target]);
                    } else {
                        imagemTransformer.nodes([e.target]);
                    }
                } else if (metaPressed && isSelected) {
                    // if we pressed keys and node was selected
                    // we need to remove it from selection:
                    const nodes = imagemTransformer.nodes().slice(); // use slice to have new copy of array
                    // remove node from array
                    nodes.splice(nodes.indexOf(e.target), 1);
                    imagemTransformer.nodes(nodes);
                } else if (metaPressed && !isSelected) {
                    if(stage.find('.texto').includes(e.target)) {
                        const nodes = textoTransformer.nodes().concat([e.target]);
                        textoTransformer.nodes(nodes);
                    } else {
                        const nodes = imagemTransformer.nodes().concat([e.target]);
                        imagemTransformer.nodes(nodes);
                    }
                }
            });

            var stage1 = new Konva.Stage({
                container: 'back',
                width: 1118,
                height: 790,
            });

            var layer1 = new Konva.Layer();
            stage1.add(layer1);

            medida = medidas.find(m => m.tipo == 8);
            if(medida === undefined)
                medida = {x: 312, y: 667, largura: 500, fontSize: 14}

            var hash = new Konva.Text({
                x: parseInt(medida.x),
                y: parseInt(medida.y),
                width: parseInt(medida.largura),
                fontSize: medida.fontSize,
                text: '$2y$10$VN/cOnYHgsW/U5W16uH.Q.MKXDNa.3Z8QqeHl89qjp/TXNLP3yNO6', //exemplo de hash
                draggable: true,
                id: 'hash',
                name: 'texto',
            });

            var textoTransformer1 = new Konva.Transformer({
                padding: 5,
                rotateEnabled: false,
                keepRatio: true,
                enabledAnchors: ['top-left', 'top-right', 'middle-right', 'middle-left', 'bottom-left', 'bottom-right'],
                // enable only side anchors
                // limit transformer size
                boundBoxFunc: (oldBox, newBox) => {
                if (newBox.width < MIN_WIDTH) {
                    return oldBox;
                }
                return newBox;
                },
            });
            layer1.add(textoTransformer1);

            var imagemTransformer1 = new Konva.Transformer({
                keepRatio: true,
                enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
            });
            layer1.add(imagemTransformer1);


            var imageObj = new Image();
            imageObj.onload = function () {
                medida = medidas.find(m => m.tipo == 7);
                // add the shape to the layer
                if(medida === undefined) {
                    yoda = new Konva.Image({
                        x: 443,
                        y: 272,
                        image: imageObj,
                        draggable: true,
                        id: 'qrcode',
                        scaleX: 0.2,
                        scaleY: 0.2,
                    });
                    yoda.setAttrs({
                        height: yoda.height() * yoda.scaleY(),
                        width: yoda.width() * yoda.scaleX(),
                        scaleX: 1,
                        scaleY: 1,
                    });
                } else {
                    medida = medidas.find(m => m.tipo == 7);
                    yoda = new Konva.Image({
                        x: parseInt(medida.x),
                        y: parseInt(medida.y),
                        image: imageObj,
                        draggable: true,
                        id: 'qrcode',
                        height: parseInt(medida.altura),
                        width: parseInt(medida.largura),
                    });
                }
                layer1.add(yoda);
                yoda.on('transform', (event) => {
                    // with enabled anchors we can only change scaleX
                    // so we don't need to reset height
                    // just width
                    if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(imagemTransformer1.getActiveAnchor()) ) {
                        event.target.setAttrs({
                            height: event.target.height() * event.target.scaleY(),
                            width: event.target.width() * event.target.scaleX(),
                            scaleX: 1,
                            scaleY: 1,
                        });
                    } else if ( ['middle-right', 'middle-left'].includes(imagemTransformer1.getActiveAnchor()) ) {
                        event.target.setAttrs({
                            width: Math.max(event.target.width() * event.target.scaleX(), MIN_WIDTH),
                            scaleX: 1,
                            scaleY: 1,
                        });
                    }
                });
            };
            imageObj.src = "/img/qrcode.png";


            hash.on('transform', (event) => {
                // with enabled anchors we can only change scaleX
                // so we don't need to reset height
                // just width
                if( ['top-left', 'top-right', 'bottom-left', 'bottom-right'].includes(textoTransformer1.getActiveAnchor()) ) {
                hash.setAttrs({
                    fontSize: Math.max(hash.fontSize() * hash.scaleX(), 2),
                    width: Math.max(hash.width() * hash.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                } else if ( ['middle-right', 'middle-left'].includes(textoTransformer1.getActiveAnchor()) ) {
                hash.setAttrs({
                    width: Math.max(hash.width() * hash.scaleX(), MIN_WIDTH),
                    scaleX: 1,
                    scaleY: 1,
                });
                }
            });
            layer1.add(hash);
            stage1.on('click tap', function (e) {

                console.log(e.target.attrs);
                // if click on empty area - remove all selections
                if (e.target === stage1) {
                    textoTransformer1.nodes([]);
                    imagemTransformer1.nodes([]);
                    return;
                }

                // do we pressed shift or ctrl?
                const metaPressed = e.evt.shiftKey || e.evt.ctrlKey || e.evt.metaKey;
                const isSelected = imagemTransformer1.nodes().indexOf(e.target) >= 0;

                if (!metaPressed && !isSelected) {
                    // if no key pressed and the node is not selected
                    // select just one
                    if(stage1.find('.texto').includes(e.target)) {
                        textoTransformer1.nodes([e.target]);
                    } else {
                        imagemTransformer1.nodes([e.target]);
                    }
                } else if (metaPressed && isSelected) {
                    // if we pressed keys and node was selected
                    // we need to remove it from selection:
                    const nodes = imagemTransformer1.nodes().slice(); // use slice to have new copy of array
                    // remove node from array
                    nodes.splice(nodes.indexOf(e.target), 1);
                    imagemTransformer1.nodes(nodes);
                } else if (metaPressed && !isSelected) {
                    if(stage1.find('.texto').includes(e.target)) {
                        const nodes = textoTransformer1.nodes().concat([e.target]);
                        textoTransformer1.nodes(nodes);
                    } else {
                        const nodes = imagemTransformer1.nodes().concat([e.target]);
                        imagemTransformer1.nodes(nodes);
                    }
                }
                });

            function send() {
                ['nome','cargo'].forEach(objeto => {
                    assinaturas.forEach(assinatura => {
                        box = stage.find('#'+objeto+''+assinatura.id);
                        document.querySelectorAll("input[name="+objeto+"-x-"+assinatura.id)[0].value = box[0].attrs.x;
                        document.querySelectorAll("input[name="+objeto+"-y-"+assinatura.id)[0].value = box[0].attrs.y;
                        document.querySelectorAll("input[name="+objeto+"-largura-"+assinatura.id)[0].value = box[0].attrs.width;
                        document.querySelectorAll("input[name="+objeto+"-fontSize-"+assinatura.id)[0].value = box[0].attrs.fontSize;
                    });
                });
                ['texto','data'].forEach(objeto => {
                    box = stage.find('#'+objeto);
                    ['x','y','largura','fontSize'].forEach(medida => {
                        document.querySelectorAll("input[name="+objeto+"-x")[0].value = box[0].attrs.x;
                        document.querySelectorAll("input[name="+objeto+"-y")[0].value = box[0].attrs.y;
                        document.querySelectorAll("input[name="+objeto+"-largura")[0].value = box[0].attrs.width;
                        document.querySelectorAll("input[name="+objeto+"-fontSize")[0].value = box[0].attrs.fontSize;
                    });
                });
                ['imagem'].forEach(objeto => {
                    assinaturas.forEach(assinatura => {
                        box = stage.find('#'+objeto+''+assinatura.id);
                        document.querySelectorAll("input[name="+objeto+"-x-"+assinatura.id)[0].value = box[0].attrs.x;
                        document.querySelectorAll("input[name="+objeto+"-y-"+assinatura.id)[0].value = box[0].attrs.y;
                        document.querySelectorAll("input[name="+objeto+"-largura-"+assinatura.id)[0].value = box[0].attrs.width;
                        document.querySelectorAll("input[name="+objeto+"-altura-"+assinatura.id)[0].value = box[0].attrs.height;
                    });
                });
                qrcode = stage1.find('#qrcode');
                document.querySelectorAll("input[name=qrcode-x")[0].value = qrcode[0].attrs.x;
                document.querySelectorAll("input[name=qrcode-y")[0].value = qrcode[0].attrs.y;
                document.querySelectorAll("input[name=qrcode-largura")[0].value = qrcode[0].attrs.width;
                document.querySelectorAll("input[name=qrcode-altura")[0].value = qrcode[0].attrs.height;

                document.querySelectorAll("input[name=hash-x")[0].value = hash.attrs.x;
                document.querySelectorAll("input[name=hash-y")[0].value = hash.attrs.y;
                document.querySelectorAll("input[name=hash-largura")[0].value = hash.attrs.width;
                document.querySelectorAll("input[name=hash-fontSize")[0].value = hash.attrs.fontSize;
                document.getElementById("form").submit();
            }
        </script>
    </body>
</html>
