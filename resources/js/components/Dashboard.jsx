import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
//mui
import dayjs from 'dayjs';
import TextField from '@mui/material/TextField';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { StaticDatePicker } from '@mui/x-date-pickers/StaticDatePicker';

function Dashboard() {

    const [valueCalendar, setValueCalendar] = useState(auxDateStart);

    const [quantityManifest, setQuantityManifest]   = useState(0);
    const [quantityInbound, setQuantityInbound]     = useState(0);
    const [quantityDispatch, setQuantityDispatch]   = useState(0);
    const [quantityDelivery, setQuantityDelivery]   = useState(0);
    const [quantityWarehouse, setQuantityWarehouse]   = useState(0);
    const [quantityFailed, setQuantityFailed]   = useState(0);


    const [listDataPie, setListDataPie] = useState([]);

    const [textLoading, setTextLoading] = useState('Loading...');

    const [loading, setLoading] = useState('block');
    const [dateStart, setDateStart] = useState(auxDateStart);
    const [dateEnd, setDateEnd] = useState(auxDateStart);
    const [dateStartReport, setDateStartReport] = useState(auxDateStart);
    const [listDataPerRoute, setListDataPerRoute] = useState([]);
    const [listDataPerTeam, setListDataPerTeam] = useState([]);
    const [listPackageRouteTotal, setListPackageRouteTotal]     = useState({
        inbound: 0,
        reinbound: 0,
        dispatch: 0,
        failed: 0,
        delivery: 0
    });
    const [listPackageTeamTotal, setListPackageTeamTotal]     = useState({
        reinbound: 0,
        dispatch: 0,
        failed: 0,
        delivery: 0
    });
    const [card, setCart] = useState('none');

    var chartPie;

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
        getDataPerDate();
    },[]);

    useEffect(() => {
        getAllQuantityStatusPackage();
        return () => {}
    }, [dateStart,dateEnd]);

    useEffect(() => {
        getDataPerDate();
        return () => {}
    }, [valueCalendar]);

    useEffect(() => {
        initPieChart();
        return () => {
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
            setQuantityWarehouse(response.quantityWarehouse);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityFailed(response.quantityFailed);
            setQuantityDelivery(response.quantityDelivery);
        });
    }

    const getDataPerDate = async () => {

        setLoading('block');
        setCart('none');

        await  fetch(`${url_general}dashboard/getDataPerDate/${valueCalendar}/`)
        .then(res => res.json())
        .then((response) => {

            let totalInboundRoute = 0;
            let totalReinboundRoute = 0;
            let totalDispatchRoute = 0;
            let totalFailedRoute = 0;
            let totalDeliveryRoute = 0;

            response.dataPerRoutes.forEach(element => {

                totalInboundRoute += element.total_inbound;
                totalReinboundRoute += element.total_reinbound;
                totalDispatchRoute += element.total_dispatch;
                totalFailedRoute += element.total_failed;
                totalDeliveryRoute += element.total_delivery;
            });

            let dataPie = [];
            dataPie.push(totalInboundRoute);
            dataPie.push(totalReinboundRoute);
            dataPie.push(totalDispatchRoute);
            dataPie.push(totalFailedRoute);
            dataPie.push(totalDeliveryRoute);
            setListDataPie(dataPie);

            let totalPackagesRoute = {
                                inbound: totalInboundRoute,
                                reinbound: totalReinboundRoute,
                                dispatch: totalDispatchRoute,
                                failed: totalFailedRoute,
                                delivery: totalDeliveryRoute
                            };

            setListPackageRouteTotal(totalPackagesRoute);
            setListDataPerRoute(response.dataPerRoutes);

              //asignando valores para data por teams

            let totalReinboundTeam = 0;
            let totalDispatchTeam = 0;
            let totalFailedTeam = 0;
            let totalDeliveryTeam = 0;

            response.dataPerTeams.forEach(element => {

                totalReinboundTeam += element.total_reinbound;
                totalDispatchTeam += element.total_dispatch;
                totalFailedTeam += element.total_failed;
                totalDeliveryTeam += element.total_delivery;
            });



            let totalPackagesTeam = {
                                reinbound: totalReinboundTeam,
                                dispatch: totalDispatchTeam,
                                failed: totalFailedTeam,
                                delivery: totalDeliveryTeam
                            };

            setListPackageTeamTotal(totalPackagesTeam);
            setListDataPerTeam(response.dataPerTeams);


        });
    }

    const listDataTablePerRoute = listDataPerRoute.map( (item, j) => {

        return (

            <tr key={j+'r'}>
                <td>
                    {j+1}
                </td>
                <td>
                    { item.Route }
                </td>
                <td className='text-end'>{ item.total_inbound }</td>
                <td className='text-end'>{ item.total_reinbound }</td>
                <td className='text-end'>{ item.total_dispatch }</td>
                <td className='text-end'>{ item.total_failed }</td>
                <td className='text-end'>{ item.total_delivery }</td>
            </tr>
        );
    });
    const listDataTablePerTeam = listDataPerTeam.map( (item, k) => {

        return (

            <tr key={k+'r'}>
                <td>
                    {k+1}
                </td>
                <td>
                    { item.name }
                </td>
                <td className='text-end'>{ item.total_reinbound }</td>
                <td className='text-end'>{ item.total_dispatch }</td>
                <td className='text-end'>{ item.total_failed }</td>
                <td className='text-end'>{ item.total_delivery }</td>
            </tr>
        );
    });

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
                '#198754',//inbound
                '#38D9A1',//re-inbound
                '#ffc107',//dispatch
                '#dc3545',//failed
                '#00c0ef'//delivery
              ],
            }],
            labels: [
              'Inbound',
              'Re-Inbound',
              'Dispatch',
              'Failed',
              'Delivery',
            ]
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

            {/* inicion segunda seccion */}
            <div className='row justify-content-center'>
                <div className='col-12'>
                    <div className='card'>
                        <div className='card-body'>
                            <div className='card-title'>
                                REPORT PER DATE
                            </div>
                            <div className='row justify-content-center '>
                                <div className='col-lg-4 col-sm-12'>
                                   <div className='row'>
                                        <div className="col-sm-12">
                                            <div className="row">
                                                {/* <div className="col-lg-12">
                                                    Date:
                                                </div> */}
                                                <div className="col-sm-12">
                                                    {/* <input type="date" className='form-control' value={ dateStartReport } onChange={ (e) => setDateStartReport(e.target.value) }/> */}
                                                    <LocalizationProvider dateAdapter={AdapterDayjs}>
                                                        <StaticDatePicker
                                                            orientation="landscape"
                                                            openTo="day"
                                                            value={valueCalendar}
                                                            // shouldDisableDate={isWeekend}
                                                            onChange={(newValue) => {
                                                            setValueCalendar(newValue);
                                                            }}
                                                            renderInput={(params) => <TextField {...params} />}
                                                        />
                                                    </LocalizationProvider>

                                                </div>
                                            </div>
                                        </div>
                                        <div className='col-sm-12 mt-2'>
                                            <h6 className="card-title"> <span>CHART PER DAY </span></h6>
                                            <canvas className="chart w-100" id="pieChart"></canvas>
                                        </div>
                                        <div className='col-sm-12 mt-2'>
                                            <h6 className="card-title "> <span>DATA TABLE PER DAY</span></h6>
                                            <div className="row form-group table-responsive">
                                                <div className="col-sm-12 table-responsive">
                                                    <table className="table table-hover table-condensed table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th style={{backgroundColor: '#fff',color: '#000'}}>#</th>
                                                                    <th style={{backgroundColor: '#fff',color: '#000'}}>TEAM</th>

                                                                    <th style={{backgroundColor: '#38D9A1',color: '#fff'}}>RE-INBOUND</th>
                                                                    <th className='bg-warning'>DISPATCH</th>
                                                                    <th className='bg-danger'>FAILED</th>
                                                                    <th className='bg-info'>DELIVERY</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr style={{backgroundColor: '#D3F7E2',color: '#000'}}>
                                                                    <td></td>
                                                                    <td><b>   TOTAL:</b></td>
                                                                    <td className='text-end'><b>{listPackageTeamTotal.reinbound}</b></td>
                                                                    <td className='text-end'><b>{listPackageTeamTotal.dispatch}</b></td>
                                                                    <td className='text-end'><b>{listPackageTeamTotal.failed}</b></td>
                                                                    <td className='text-end'><b>{listPackageTeamTotal.delivery}</b></td>
                                                                </tr>
                                                                { listDataTablePerTeam }
                                                            </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                   </div>

                                </div>
                                <div className='col-lg-4 col-sm-12'>
                                    <h6 className="card-title "> <span>DATA TABLE PER DAY</span></h6>
                                    <div className="row form-group table-responsive">
                                        <div className="col-sm-12">
                                            <table className="table table-hover table-condensed table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th style={{backgroundColor: '#fff',color: '#000'}}>#</th>
                                                            <th style={{backgroundColor: '#fff',color: '#000'}}>ROUTE</th>
                                                            <th className='bg-success'>INBOUND</th>
                                                            <th style={{backgroundColor: '#38D9A1',color: '#fff'}}>RE-INBOUND</th>
                                                            <th className='bg-warning'>DISPATCH</th>
                                                            <th className='bg-danger'>FAILED</th>
                                                            <th className='bg-info'>DELIVERY</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr style={{backgroundColor: '#D3F7E2',color: '#000'}}>
                                                            <td></td>
                                                            <td><b>   TOTAL:</b></td>
                                                            <td className='text-end'><b>{listPackageRouteTotal.inbound}</b></td>
                                                            <td className='text-end'><b>{listPackageRouteTotal.reinbound}</b></td>
                                                            <td className='text-end'><b>{listPackageRouteTotal.dispatch}</b></td>
                                                            <td className='text-end'><b>{listPackageRouteTotal.failed}</b></td>
                                                            <td className='text-end'><b>{listPackageRouteTotal.delivery}</b></td>
                                                        </tr>
                                                        { listDataTablePerRoute }
                                                    </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
