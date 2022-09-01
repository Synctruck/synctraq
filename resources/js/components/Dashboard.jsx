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

    const [listQuantityRoute, setListQuantityRoute] = useState([]);
    const [listDataPie, setListDataPie] = useState([]);

    const [textLoading, setTextLoading] = useState('Loading...');

    const [loading, setLoading] = useState('block');
    const [dateStart, setDateStart] = useState(auxDateStart);
    const [dateEnd, setDateEnd] = useState(auxDateStart);
    const [card, setCart] = useState('none');

    var chartBar;
    var chartPie;

    useEffect(() => {

    }, []);
    useEffect(() => {

        getAllQuantityStatusPackage();
        return () => {}
    }, [dateStart,dateEnd]);

    useEffect(() => {
        initPieChart();
        initBarChart();
        return () => {
            chartBar.destroy();
            chartPie.destroy();
        }
    },[listDataPie]);

    const getAllQuantityStatusPackage = async () => {

        setLoading('block');
        setCart('none');

        await  fetch(`${url_general}dashboard/getallquantity/${dateStart}/${dateEnd}`)
        .then(res => res.json())
        .then((response) => {

            setQuantityManifest(response.quantityManifest);
            setQuantityInbound(response.quantityInbound);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityDelivery(response.quantityDelivery);
            setQuantityWarehouse(response.quantityWarehouse);

            setListDataPie([]);
            let dataPie = [];

            dataPie.push(response.quantityManifest);
            dataPie.push(response.quantityInbound);
            dataPie.push(response.quantityDispatch);
            dataPie.push(response.quantityDelivery);
            dataPie.push(response.quantityWarehouse);

            setListDataPie(dataPie);
            console.log(listDataPie);

            setLoading('none');
            setCart('block');
        });
    }

    function initPieChart() {
        //-------------
        //- PIE CHART -
        //-------------
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

        var ctx = document.getElementById("pieChart");
        chartPie = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: listDataPie,
              backgroundColor: [
                '#0d6efd',
                '#198754',
                '#ffc107',
                '#00c0ef',
                '#f56954'
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


    function initBarChart () {
        //-------------
        //- BAR CHART -
        //-------------
        var areaChartData = {
          labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
          datasets: [
            {
              label               : 'Electronics',
              backgroundColor     : '#f56954',
              data                : [65, 59, 80, 81, 56, 55, 40]
            },
            {
              label               : 'Fashion',
              backgroundColor     : '#00a65a',
              data                : [28, 48, 40, 19, 86, 27, 90]
            },
            {
              label               : 'Foods',
              backgroundColor     : '#00c0ef',
              data                : [70, 60, 65, 50, 60, 70, 80]
            },
            {
              label               : 'Foods',
              backgroundColor     : '#8E44AD',
              data                : [70, 60, 65, 50, 60, 70, 80]
            }
          ]
        }
        var barChartOptions = {
          //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
          scaleBeginAtZero        : true,
          //Boolean - Whether grid lines are shown across the chart
          scaleShowGridLines      : true,
          //String - Colour of the grid lines
          scaleGridLineColor      : 'rgba(0,0,0,.05)',
          //Number - Width of the grid lines
          scaleGridLineWidth      : 1,
          //Boolean - Whether to show horizontal lines (except X axis)
          scaleShowHorizontalLines: true,
          //Boolean - Whether to show vertical lines (except Y axis)
          scaleShowVerticalLines  : true,
          //Boolean - If there is a stroke on each bar
          barShowStroke           : true,
          //Number - Pixel width of the bar stroke
          barStrokeWidth          : 2,
          //Number - Spacing between each of the X value sets
          barValueSpacing         : 5,
          //Number - Spacing between data sets within X values
          barDatasetSpacing       : 1,
          //String - A legend template
          responsive              : true,
          maintainAspectRatio     : true,
          indexAxis: 'y',
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

        var ctxBar = document.getElementById("barChart");

        chartBar = new Chart(ctxBar, {
          type: 'bar',
          data: areaChartData,
          options: barChartOptions
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
                                {/* <div className="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div className="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="card-icon rounded-circle-dashboard rounded-circle-primary d-flex align-items-center justify-content-center">
                                                        <i className="bx bxs-box" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 className="card-title">Manifest</h5>
                                                    <h4>{ quantityManifest }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div className="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="card-icon rounded-circle-dashboard rounded-circle-success d-flex align-items-center justify-content-center">
                                                        <i className="bx bx-barcode-reader" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 className="card-title">Inbound</h5>
                                                    <h4>{ quantityInbound }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div className="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div className="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="card-icon rounded-circle-dashboard rounded-circle-warning d-flex align-items-center justify-content-center">
                                                        <i className="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 className="card-title">Dispatch</h5>
                                                    <h4>{ quantityDispatch }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div className="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div className="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="card-icon rounded-circle-dashboard bg-info d-flex align-items-center justify-content-center">
                                                        <i className="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 className="card-title">Delivery</h5>
                                                    <h4>{ quantityDelivery }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div className="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="card-icon rounded-circle-dashboard rounded-circle-danger d-flex align-items-center justify-content-center">
                                                        <i className="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 className="card-title">Warehouse</h5>
                                                    <h4>{ quantityWarehouse }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> */}
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
                                    <div className="card text-white bg-danger mb-3" style={{maxWidth: '18rem'}} >
                                        <div className="card-header bg-danger text-white text-start">  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Warehouse</div>
                                        <div className="card-body">
                                            <h3 className=" text-white text-start">{ quantityWarehouse }</h3>
                                        </div>
                                        <a className="card-footer text-end bg-danger text-white" href="/package-warehouse">
                                            More info <i className='bi bi-arrow-right-circle'></i>
                                        </a>
                                    </div>
                                </div>



                            </div>

                            <div className="row form-group" style={ {display: 'none'} }>
                                <div className="col-lg-2 text-center">
                                    <div className="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div className="card-body">
                                            <h5 className="card-title">FEDEX  <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center">
                                    <div className="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div className="card-body">
                                            <h5 className="card-title">UPS   <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center">
                                    <div className="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div className="card-body">
                                            <h5 className="card-title">DHL   <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-2 text-center">
                                    <div className="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.1rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div className="card-body">
                                            <h5 className="card-title">USPS  <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            <div className='row justify-content-center'>
                    {/* <div className='col-8'>
                    <div className='card'>

                        < div className='card-body'>
                            <h5 className="card-title">Reports <span>/Bar</span></h5>
                            <canvas className="chart w-100" id="barChart"></canvas>

                        </div>
                    </div>
                </div> */}
                <div className='col-6'>
                    <div className='card'>

                        <div className='card-body'>
                            <h5 className="card-title">Reports <span>/Pie</span></h5>
                            <canvas className="chart w-100" id="pieChart"></canvas>
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
