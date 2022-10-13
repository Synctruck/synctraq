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
import moment from 'moment';
import { Grid } from '@mui/material'
import { CalendarPicker } from '@mui/x-date-pickers'

// moment().format();

function PartnerDashboard() {

    const [valueCalendar, setValueCalendar] = React.useState(dayjs());
    // const [days, setDays] = useState(dayjs(auxDateStart));

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
        getAllQuantityStatusPackage();
        return () => {}
    }, [dateStart,dateEnd]);




    const getAllQuantityStatusPackage = async () => {
        setLoading('block');
        setCart('none');

        await  fetch(`${url_general}partners/dashboard/getallquantity/${dateStart}/${dateEnd}`)
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

    const [packageHistoryList, setPackageHistoryList] = useState([]);
    const [packageStateList, setPackageStateList] = useState([]);

    const getDataPerDate = async () => {
        setLoading('block');
        setCart('none');

        await  fetch(`${url_general}partners/dashboard/getDataPerDate/${valueCalendar}/`)
        .then(res => res.json())
        .then((response) => {

            let totalInboundRoute   = 0;
            let totalReinboundRoute = 0;
            let totalReturn         = 0;
            let totalDispatchRoute  = 0;
            let totalFailedRoute    = 0;
            let totalDeliveryRoute  = 0;

            let listReportPerRoute = [];

            response.packageRouteList.forEach( route => {

                let quantityInboundRoute = 0;
                let quantityReinboundRoute = 0;
                let quantityDispatchRoute = 0;
                let quantityFailedRoute = 0;
                let quantityDeliveryRoute = 0;

                response.packageHistoryInbound.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route)
                    {
                        quantityInboundRoute++;
                    }
                });

                response.packageHistoryListProcess.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route && packageHistory.status == 'ReInbound')
                    {
                        quantityReinboundRoute++;
                    }
                    else if(packageHistory.Route == route.Route && packageHistory.status == 'Dispatch')
                    {
                        quantityDispatchRoute++;
                    }
                    else if(packageHistory.Route == route.Route && packageHistory.status == 'Failed')
                    {
                        quantityFailedRoute++;
                    }
                    else if(packageHistory.Route == route.Route && packageHistory.status == 'Delivery')
                    {
                        quantityDeliveryRoute++;
                    }

                    if(packageHistory.Route == route.Route && packageHistory.status == 'Return')
                    {
                        totalReturn++;
                    }
                });

                totalInboundRoute   = parseInt(totalInboundRoute) + parseInt(quantityInboundRoute);
                totalReinboundRoute = parseInt(totalReinboundRoute) + parseInt(quantityReinboundRoute);
                totalDispatchRoute  = parseInt(totalDispatchRoute) + parseInt(quantityDispatchRoute);
                totalFailedRoute    = parseInt(totalFailedRoute) + parseInt(quantityFailedRoute);
                totalDeliveryRoute  = parseInt(totalDeliveryRoute) + parseInt(quantityDeliveryRoute);

                const data = {

                    Route: route.Route,
                    total_inbound: quantityInboundRoute,
                    total_reinbound: quantityReinboundRoute,
                    total_dispatch: quantityDispatchRoute,
                    total_failed: quantityFailedRoute,
                    total_delivery: quantityDeliveryRoute,
                }

                listReportPerRoute.push(data);

                //console.log('Route: '+ route.Route +', Inbound: '+ quantityInboundRoute +', ReInbound: '+ quantityReinboundRoute +', Dispatch: '+ quantityDispatchRoute +', Failed: '+ quantityFailedRoute +', Delivery: '+ quantityDeliveryRoute);
            });

            console.log('=======================');
            console.log('totalReturn: '+ totalReturn);
            console.log('totalReinboundRoute: '+ totalReinboundRoute);
            console.log('=======================');

            let totalPackagesRoute = {

                inbound: totalInboundRoute,
                reinbound: parseInt(totalReinboundRoute) + parseInt(totalReturn),
                dispatch: totalDispatchRoute,
                failed: totalFailedRoute,
                delivery: totalDeliveryRoute
            };

            setListPackageRouteTotal(totalPackagesRoute);
            setListDataPerRoute(listReportPerRoute);

            let dataPie = [];

            dataPie.push(totalInboundRoute);
            dataPie.push(totalReinboundRoute);
            dataPie.push(totalDispatchRoute);
            dataPie.push(totalFailedRoute);
            dataPie.push(totalDeliveryRoute);

            setListDataPie(dataPie);

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

            /*response.dataPerRoutes.forEach(element => {

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
            setListDataPerTeam(response.dataPerTeams);*/


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
                                <div className="col-lg-4">
                                    <div className="row justify-content-center">
                                        <div className='col-lg-12 text-center'> <h6> Date between : {moment(dateStart).subtract(1,'days').format('LL')} And {moment(dateEnd).subtract(1,'days').format('LL')}</h6></div>
                                        <div className="col-lg-6 text-center form-group">
                                            <div className="card text-white bg-primary mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header bg-primary text-white text-start">  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Manifest</div>
                                                <div className="card-body">
                                                    <h3 className=" text-white text-start">{ quantityManifest }</h3>
                                                </div>
                                                <a className="card-footer text-end bg-primary text-white" href="/package-manifest">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
                                        </div>
                                        <div className="col-lg-6 text-center form-group">
                                            <div className="card text-white bg-success mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header bg-success text-white text-start">  <i className="bx bx-barcode-reader" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Inbound </div>
                                                <div className="card-body">
                                                    <h3 className=" text-white text-start">{ quantityInbound}</h3>
                                                </div>
                                                <a className="card-footer text-end bg-success text-white" href="/package-inbound">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-8">
                                    <div className="row justify-content-center">
                                        <div className='col-lg-12 text-center'> <h6> Date between : {moment(dateStart).format('LL')} And {moment(dateEnd).format('LL')}</h6></div>

                                        <div className="col-lg-3 text-center form-group">
                                            <div className="card text-white bg-warning mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header bg-warning text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Dispatch</div>
                                                <div className="card-body">
                                                    <h3 className=" text-white text-start">{ quantityDispatch}</h3>
                                                </div>
                                                <a className="card-footer text-end bg-warning text-white" href="/package-dispatch">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
                                        </div>
                                        <div className="col-lg-3 text-center form-group">
                                            <div className="card text-white bg-danger mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header bg-danger text-white text-start">  <i className="bx bxs-error-alt" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Failed</div>
                                                <div className="card-body">
                                                    <h3 className=" text-white text-start">{ quantityFailed}</h3>
                                                </div>
                                                <a className="card-footer text-end bg-danger text-white" href="/package-dispatch">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
                                        </div>
                                        <div className="col-lg-3 text-center form-group">
                                            <div className="card text-white bg-info mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header bg-info text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Delivery</div>
                                                <div className="card-body">
                                                    <h3 className=" text-white text-start">{ quantityDelivery }</h3>
                                                </div>
                                                <a className="card-footer text-end bg-info text-white" href="#">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
                                        </div>
                                        <div className="col-lg-3 text-center form-group">
                                            <div className="card text-white mb-3" style={{maxWidth: '18rem'}} >
                                                <div className="card-header  text-white text-start" style={{background:'#5b0672'}}>  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: '',background:'#5b0672'} }></i> Warehouse</div>
                                                <div className="card-body" style={{background:'#5b0672'}}>
                                                    <h3 className=" text-white text-start">{ quantityWarehouse }</h3>
                                                </div>
                                                <a className="card-footer text-end text-white" style={{background:'#5b0672'}} href="/package-warehouse">
                                                    {/* More info <i className='bi bi-arrow-right-circle'></i> */}
                                                </a>
                                            </div>
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

export default PartnerDashboard;

if (document.getElementById('partnerDashboard')) {
    ReactDOM.render(<PartnerDashboard />, document.getElementById('partnerDashboard'));
}
