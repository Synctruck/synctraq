import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Dashboard() {

    // const [quantityManifest, setQuantityManifest]   = useState(0);
    // const [quantityInbound, setQuantityInbound]     = useState(0);
    // const [quantityNotExists, setQuantityNotExists] = useState(0);
    // const [quantityDispatch, setQuantityDispatch]   = useState(0);
    // const [quantityReturn, setQuantityReturn]       = useState(0);
    // const [quantityDelivery, setQuantityDelivery]   = useState(0);

    const [quantityManifest, setQuantityManifest]   = useState(0);
    const [quantityInbound, setQuantityInbound]     = useState(0);
    const [quantityDispatch, setQuantityDispatch]   = useState(0);
    const [quantityDelivery, setQuantityDelivery]   = useState(0);
    const [quantityWarehouse, setQuantityWarehouse]   = useState(0);
    const [quantityFailed, setQuantityFailed]   = useState(0);

    const [listColorsForManifest, setListColorsForManifest] = useState([]);
    const [listRoutesForManifest, setListRoutesForManifest] = useState([]);
    const [lisValuesForManifest, setLisValuesForManifest] = useState([]);

    const [listColorsForInbound, setListColorsForInbound] = useState([]);
    const [listRoutesForInbound, setListRoutesForInbound] = useState([]);
    const [lisValuesForInbound, setLisValuesForInbound] = useState([]);

    const [listColorsForDispatch, setListColorsForDispatch] = useState([]);
    const [listRoutesForDispatch, setListRoutesForDispatch] = useState([]);
    const [lisValuesForDispatch, setLisValuesForDispatch] = useState([]);

    const [listColorsForFailed, setListColorsForFailed] = useState([]);
    const [listRoutesForFailed, setListRoutesForFailed] = useState([]);
    const [lisValuesForFailed, setLisValuesForFailed] = useState([]);

    const [listColorsForDelivery, setListColorsForDelivery] = useState([]);
    const [listRoutesForDelivery, setListRoutesForDelivery] = useState([]);
    const [lisValuesForDelivery, setLisValuesForDelivery] = useState([]);

    const [listColorsForWarehouse, setListColorsForWarehouse] = useState([]);
    const [listRoutesForWarehouse, setListRoutesForWarehouse] = useState([]);
    const [lisValuesForWarehouse, setLisValuesForWarehouse] = useState([]);



    const [listDataPie, setListDataPie] = useState([]);

    const [textLoading, setTextLoading] = useState('Loading...');

    const [loading, setLoading] = useState('block');
    const [dateStart, setDateStart] = useState(auxDateStart);
    const [dateEnd, setDateEnd] = useState(auxDateStart);
    const [card, setCart] = useState('none');


    var chartPie;
    var chartPieManifest;
    var chartPieInbound;
    var chartPieDispatch;
    var chartPieFailed;
    var chartPieDelivery;
    var chartPieWarehouse;

    var pieOptions = {
        responsive: true,
        segmentShowStroke: true,
        segmentStrokeColor: '#fff',
        segmentStrokeWidth: 1,
        animationSteps: 100,
        animationEasing: 'easeOutBounce',
        animateRotate: true,
        animateScale: true,
        maintainAspectRatio: true,
        legend: {
          display: true,
          position: 'right',
          labels: {
            boxWidth: 15,
            defaultFontColor: '#343a40',
            defaultFontSize: 11,
          }
        }
      }

    useEffect(() => {

    }, []);
    useEffect(() => {

        getAllQuantityStatusPackage();
        return () => {}
    }, [dateStart,dateEnd]);

    useEffect(() => {
        initPieChart();
        initPieChartManifest();
        initPieChartInbound();
        initPieChartDispatch();
        initPieChartFailed();
        initPieChartDelivery();
        initPieChartWarehouse();
        return () => {
            chartPie.destroy();
            chartPieManifest.destroy();
            chartPieInbound.destroy();
            chartPieDispatch.destroy();
            chartPieFailed.destroy();
            chartPieDelivery.destroy();
            chartPieWarehouse.destroy();
        }
    },[listDataPie,listColorsForManifest,listColorsForInbound,listColorsForDispatch,listColorsForFailed,listColorsForDelivery,listColorsForWarehouse]);

    const getAllQuantityStatusPackage = async () => {

        setLoading('block');
        setCart('none');

        await  fetch(`${url_general}dashboard/getallquantity/${dateStart}/${dateEnd}`)
        .then(res => res.json())
        .then((response) => {

            setQuantityManifest(response.quantityManifest);
            setQuantityInbound(response.quantityInbound);
            setQuantityWarehouse(response.quantityWarehouse);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityFailed(response.quantityFailed);
            setQuantityDelivery(response.quantityDelivery);

            //asignando valores al pie general
            setListDataPie([]);
            let dataPie = [];
            dataPie.push(response.quantityManifest);
            dataPie.push(response.quantityInbound);
            dataPie.push(response.quantityWarehouse);
            dataPie.push(response.quantityDispatch);
            dataPie.push(response.quantityFailed);
            dataPie.push(response.quantityDelivery);
            setListDataPie(dataPie);

            let arrayNamesRoutesManifest = [];
            let arrayValuesRoutesManifest = [];
            let arrayColorsRoutesManifest = [];
            response.quantityManifestByRoutes.forEach(element => {
                arrayNamesRoutesManifest.push(element.Route);
                arrayValuesRoutesManifest.push(element.total);
                arrayColorsRoutesManifest.push(generarColorAleatorio());
            });

            setListRoutesForManifest(arrayNamesRoutesManifest);
            setLisValuesForManifest(arrayValuesRoutesManifest);
            setListColorsForManifest(arrayColorsRoutesManifest);
            //fin

            //inicion
            let arrayNamesRoutesInbound = [];
            let arrayValuesRoutesInbound = [];
            let arrayColorsRoutesInbound = [];
            response.quantityInboundByRoutes.forEach(element => {
                arrayNamesRoutesInbound.push(element.Route);
                arrayValuesRoutesInbound.push(element.total);
                arrayColorsRoutesInbound.push(generarColorAleatorio());
            });

            setListRoutesForInbound(arrayNamesRoutesInbound);
            setLisValuesForInbound(arrayValuesRoutesInbound);
            setListColorsForInbound(arrayColorsRoutesInbound);
             //fin

            //inicion
            let arrayNamesRoutesDispatch = [];
            let arrayValuesRoutesDispatch = [];
            let arrayColorsRoutesDispatch = [];
            response.quantityDispatchByRoutes.forEach(element => {
                arrayNamesRoutesDispatch.push(element.Route);
                arrayValuesRoutesDispatch.push(element.total);
                arrayColorsRoutesDispatch.push(generarColorAleatorio());
            });

            setListRoutesForDispatch(arrayNamesRoutesDispatch);
            setLisValuesForDispatch(arrayValuesRoutesDispatch);
            setListColorsForDispatch(arrayColorsRoutesDispatch);
            //fin

            //inicion
            let arrayNamesRoutesFailed = [];
            let arrayValuesRoutesFailed = [];
            let arrayColorsRoutesFailed = [];
            response.quantityFailedByRoutes.forEach(element => {
                arrayNamesRoutesFailed.push(element.Route);
                arrayValuesRoutesFailed.push(element.total);
                arrayColorsRoutesFailed.push(generarColorAleatorio());
            });

            setListRoutesForFailed(arrayNamesRoutesFailed);
            setLisValuesForFailed(arrayValuesRoutesFailed);
            setListColorsForFailed(arrayColorsRoutesFailed);

             //fin

            //inicion
            let arrayNamesRoutesDelivery = [];
            let arrayValuesRoutesDelivery = [];
            let arrayColorsRoutesDelivery = [];
            response.quantityDeliveryByRoutes.forEach(element => {
                arrayNamesRoutesDelivery.push(element.Route);
                arrayValuesRoutesDelivery.push(element.total);
                arrayColorsRoutesDelivery.push(generarColorAleatorio());
            });

            setListRoutesForDelivery(arrayNamesRoutesDelivery);
            setLisValuesForDelivery(arrayValuesRoutesDelivery);
            setListColorsForDelivery(arrayColorsRoutesDelivery);
             //fin

            //inicion
            let arrayNamesRoutesWarehouse = [];
            let arrayValuesRoutesWarehouse = [];
            let arrayColorsRoutesWarehouse = [];
            response.quantityWarehouseByRoutes.forEach(element => {
                arrayNamesRoutesWarehouse.push(element.Route);
                arrayValuesRoutesWarehouse.push(element.total);
                arrayColorsRoutesWarehouse.push(generarColorAleatorio());
            });

            setListRoutesForWarehouse(arrayNamesRoutesWarehouse);
            setLisValuesForWarehouse(arrayValuesRoutesWarehouse);
            setListColorsForWarehouse(arrayColorsRoutesWarehouse);

        });
    }

    function colorAleatorio(inferior,superior){
        let numPosibilidades = superior - inferior
        let aleat = Math.random() * numPosibilidades
        aleat = Math.floor(aleat)
        return parseInt(inferior) + aleat
    }

    function generarColorAleatorio(){
       let  hexadecimal = new Array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F")
        let resultado = "#";
        for (let i=0;i<6;i++){
           let posarray = colorAleatorio(0,hexadecimal.length)
           resultado += hexadecimal[posarray]
        }
        return resultado
     }

    function initPieChart() {
        //-------------
        //- PIE CHART -
        //-------------

        var ctx = document.getElementById("pieChart");
        chartPie = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: listDataPie,
              backgroundColor: [
                '#0d6efd',//manifest
                '#198754',//inbound
                '#5b0672',//warehouse
                '#ffc107',//dispatch
                '#4B79EA',//failed
                '#00c0ef'//delivery
              ],
            }],
            labels: [
              'Manifest',
              'Inbound',
              'Dispatch',
              'Delivery',
              'Warehouse'
            ]
          },
          options: pieOptions
        });
    }

    function initPieChartManifest() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartManifest");
        chartPieManifest = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForManifest,
              backgroundColor: listColorsForManifest,
            }],
            labels: listRoutesForManifest,
          },
          options: pieOptions
        });
    }

    function initPieChartInbound() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartInbound");
        chartPieInbound = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForInbound,
              backgroundColor: listColorsForInbound,
            }],
            labels: listRoutesForInbound,
          },
          options: pieOptions
        });
    }

    function initPieChartDispatch() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartDispatch");
        chartPieDispatch = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForDispatch,
              backgroundColor: listColorsForDispatch,
            }],
            labels: listRoutesForDispatch,
          },
          options: pieOptions
        });
    }

    function initPieChartFailed() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartFailed");
        chartPieFailed = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForFailed,
              backgroundColor: listColorsForFailed,
            }],
            labels: listRoutesForFailed,
          },
          options: pieOptions
        });
    }

    function initPieChartDelivery() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartDelivery");
        chartPieDelivery = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForDelivery,
              backgroundColor: listColorsForDelivery,
            }],
            labels: listRoutesForDelivery,
          },
          options: pieOptions
        });
    }

    function initPieChartWarehouse() {
        //-------------
        //- PIE CHART Manifest-
        //-------------
        var ctx = document.getElementById("pieChartWarehouse");
        chartPieWarehouse = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: lisValuesForWarehouse,
              backgroundColor: listColorsForWarehouse,
            }],
            labels: listRoutesForWarehouse,
          },
          options: pieOptions
        });
    }



    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">

                        <div className="card-body" >
                            <div className="row mb-4">
                                <div className="col-lg-2">
                                    <div className="row">
                                        <div className="col-lg-12">
                                            Start date:
                                        </div>
                                        <div className="col-lg-12">
                                            <input type="date" className='form-control' value={ dateStart } onChange={ (e) => setDateStart(e.target.value) }/>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-2">
                                    <div className="row">
                                        <div className="col-lg-12">
                                            End date :
                                        </div>
                                        <div className="col-lg-12">
                                            <input type="date" className='form-control' value={ dateEnd } onChange={ (e) => setDateEnd(e.target.value) }/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row justify-content-center">

                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white bg-primary mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-primary text-white text-start">  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Manifest</div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityManifest }</h3>
                                        </div>
                                        <a className="card-footer text-end bg-primary text-white" href="/package-manifest">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white bg-success mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-success text-white text-start">  <i className="bx bx-barcode-reader" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Inbound </div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityInbound}</h3>
                                        </div>
                                        <a className="card-footer text-end bg-success text-white" href="/package-inbound">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white bg-warning mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-warning text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Dispatch</div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityDispatch}</h3>
                                        </div>
                                        <a className="card-footer text-end bg-warning text-white" href="/package-dispatch">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white bg-danger mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-danger text-white text-start">  <i className="bx bxs-error-alt" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Failed</div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityFailed}</h3>
                                        </div>
                                        <a className="card-footer text-end bg-danger text-white" href="/package-dispatch">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white bg-info mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-info text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Delivery</div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityDelivery }</h3>
                                        </div>
                                        <a className="card-footer text-end bg-info text-white" href="#">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center form-group">
                                    <div className="card text-white mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header  text-white text-start" style={{background:'#5b0672'}}>  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: '',background:'#5b0672'} }></i> Warehouse</div>
                                        <div className="card-body" style={{background:'#5b0672'}}>
                                            <h3 className=" text-white text-start">{ quantityWarehouse }</h3>
                                        </div>
                                        <a className="card-footer text-end text-white" style={{background:'#5b0672'}} href="/package-warehouse">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div className='row justify-content-center'>
                <div className='col-6'>
                    <div className='card'>
                        <div className='card-body'>
                            <h5 className="card-title">Report <span>/General</span></h5>
                            <canvas className="chart w-100" id="pieChart"></canvas>
                        </div>
                    </div>
                </div>

                <div className='col-6'>
                    <div className='card'>
                        <div className='card-body'>
                            <h5 className="card-title">Report <span>/Manifest</span></h5>
                            <canvas className="chart w-100" id="pieChartManifest"></canvas>
                        </div>
                    </div>
                </div>
                <div className='col-6'>
                    <div className='card'>
                        < div className='card-body'>
                            <h5 className="card-title">Report <span>/Inbound</span></h5>
                            <canvas className="chart w-100" id="pieChartInbound"></canvas>
                        </div>
                    </div>
                </div>
                <div className='col-6'>
                    <div className='card'>
                        < div className='card-body'>
                            <h5 className="card-title">Report <span>/Dispatch</span></h5>
                            <canvas className="chart w-100" id="pieChartDispatch"></canvas>
                        </div>
                    </div>
                </div>
                <div className='col-6'>
                    <div className='card'>
                        < div className='card-body'>
                            <h5 className="card-title">Report <span>/Failed</span></h5>
                            <canvas className="chart w-100" id="pieChartFailed"></canvas>
                        </div>
                    </div>
                </div>
                <div className='col-6'>
                    <div className='card'>
                        < div className='card-body'>
                            <h5 className="card-title">Report <span>/Delivery</span></h5>
                            <canvas className="chart w-100" id="pieChartDelivery"></canvas>
                        </div>
                    </div>
                </div>
                <div className='col-6'>
                    <div className='card'>
                        < div className='card-body'>
                            <h5 className="card-title">Report <span>/Warehouse</span></h5>
                            <canvas className="chart w-100" id="pieChartWarehouse"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    );
}

export default Dashboard;

// DOM element
if (document.getElementById('dashboard')) {
    ReactDOM.render(<Dashboard />, document.getElementById('dashboard'));
}
