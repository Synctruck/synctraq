import React, { useState, useEffect, useRef } from 'react'
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
import ReactLoading from 'react-loading';
import { DownloadTableExcel } from 'react-export-table-to-excel';
// moment().format();

function Dashboard() {

    const [valueCalendar, setValueCalendar] = React.useState(dayjs()); 
    // const [days, setDays] = useState(dayjs(auxDateStart));

    const [quantityManifest, setQuantityManifest]   = useState(0);
    const [quantityInbound, setQuantityInbound]     = useState(0);
    const [quantityDispatch, setQuantityDispatch]   = useState(0);
    const [quantityReInbound, setQuantityReInbound] = useState(0);
    const [quantityDelivery, setQuantityDelivery]   = useState(0);
    const [quantityWarehouse, setQuantityWarehouse] = useState(0);
    const [quantityFailed, setQuantityFailed]       = useState(0);

    const tableRef = useRef(null);

    const [listDataPie, setListDataPie] = useState([]);

    const [textLoading, setTextLoading] = useState('Loading...');

    const [loading, setLoading]     = useState('block');
    const [dateStart, setDateStart] = useState(auxDateStart);
    const [dateEnd, setDateEnd]     = useState(auxDateStart);

    const [dateStartTable, setDateStartTable] = useState(auxDateStart);
    const [dateEndTable, setDateEndTable]     = useState(auxDateStart);

    const [dateStartReport, setDateStartReport] = useState(auxDateStart);
    const [listDataPerRoute, setListDataPerRoute] = useState([]);
    const [listDataPerTeam, setListDataPerTeam] = useState([]);

    const [isLoading, setIsLoading] = useState(false);

    const [listPackageRouteTotal, setListPackageRouteTotal]     = useState({
        inbound: 0,
        warehouse: 0,
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

    /*useEffect(() => {
        getDataPerDate();
    },[]);*/

    useEffect(() => {
        getAllQuantityStatusPackage();
        return () => {}
    }, [dateStart,dateEnd]);

    useEffect(() => {
        getDataPerDate();
        return () => {}
    }, [dateStartTable, dateEndTable]);

    useEffect(() => {
        //initPieChart();
        return () => {
            //chartPie.destroy();
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
            setQuantityReInbound(response.quantityReInbound);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityFailed(response.quantityFailed);
            setQuantityDelivery(response.quantityDelivery);
        });
    }

    const [packageHistoryList, setPackageHistoryList] = useState([]);
    const [packageStateList, setPackageStateList] = useState([]);

    const sortHelper = (data, sortBy) => {
      if (data.find(x => typeof x[sortBy] !== 'number')) 
        // sort by localeCompare
        return data.sort((a,b) => a[sortBy].localeCompare(b[sortBy]))
      else
        // sort by number
        return data.sort((a,b) => a[sortBy] - b[sortBy])
    }

    const getDataPerDate = async () => {
        
        setLoading('block');
        setCart('none');
        setIsLoading(true);

        await  fetch(`${url_general}dashboard/getDataPerDate/${dateStartTable}/${dateEndTable}`)
        .then(res => res.json())
        .then((response) => {

            let totalInboundRoute    = 0;
            let totalWarehouseRoute  = 0;
            let totalMiddleMileRoute = 0;
            let totalReinboundRoute  = 0;
            let totalReturn          = 0;
            let totalDispatchRoute   = 0;
            let totalFailedRoute     = 0;
            let totalDeliveryRoute   = 0;

            let listReportPerRoute = [];

            response.packageRouteList.forEach( route => {

                let quantityInboundRoute    = 0;
                let quantityWarehouseRoute  = 0;
                let quantityMiddleMileRoute = 0;
                let quantityReinboundRoute  = 0;
                let quantityDispatchRoute   = 0;
                let quantityFailedRoute     = 0;
                let quantityDeliveryRoute   = 0;

                response.packageHistoryInbound.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route)
                    {
                        quantityInboundRoute++;
                    }
                });

                response.packageHistoryWarehouse.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route)
                    {
                        quantityWarehouseRoute++;
                    }
                });

                response.packageHistoryMiddleMileScan.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route)
                    {
                        quantityMiddleMileRoute++;
                    }
                });

                response.packageDispatchList.forEach( packageDispatch => {

                    if(packageDispatch.Route == route.Route)
                    {
                        quantityDispatchRoute++;
                    }
                });

                response.packageDeliveryList.forEach( packageDelivery => {

                    if(packageDelivery.Route == route.Route)
                    {
                        quantityDeliveryRoute++;
                    }
                });

                response.packageHistoryListProcess.forEach( packageHistory => {

                    if(packageHistory.Route == route.Route && packageHistory.status == 'ReInbound')
                    {
                        quantityReinboundRoute++;
                    }
                    else if(packageHistory.Route == route.Route && packageHistory.status == 'Failed')
                    {
                        quantityFailedRoute++;
                    }

                    if(packageHistory.Route == route.Route && packageHistory.status == 'Return')
                    {
                        totalReturn++;
                    }
                });

                totalInboundRoute    = parseInt(totalInboundRoute) + parseInt(quantityInboundRoute);
                totalWarehouseRoute  = parseInt(totalWarehouseRoute) + parseInt(quantityWarehouseRoute)
                totalMiddleMileRoute = parseInt(totalMiddleMileRoute) + parseInt(quantityMiddleMileRoute)
                totalReinboundRoute  = parseInt(totalReinboundRoute) + parseInt(quantityReinboundRoute);
                totalDispatchRoute   = parseInt(totalDispatchRoute) + parseInt(quantityDispatchRoute);
                totalFailedRoute     = parseInt(totalFailedRoute) + parseInt(quantityFailedRoute);
                totalDeliveryRoute   = parseInt(totalDeliveryRoute) + parseInt(quantityDeliveryRoute);

                const data = {

                    Route: route.Route,
                    total_inbound: quantityInboundRoute,
                    total_warehouse: quantityWarehouseRoute,
                    total_middle: quantityMiddleMileRoute,
                    total_pending: quantityInboundRoute + quantityWarehouseRoute + quantityMiddleMileRoute,
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
                warehouse: totalWarehouseRoute,
                middlemilescan: totalMiddleMileRoute,
                pending: parseInt(totalInboundRoute) + parseInt(totalWarehouseRoute) + parseInt(totalMiddleMileRoute),
                dispatch: totalDispatchRoute,
                failed: totalFailedRoute,
                delivery: totalDeliveryRoute
            };
            
            sortHelper(listReportPerRoute, 'total_pending').reverse()
            setListPackageRouteTotal(totalPackagesRoute);
            setListDataPerRoute(listReportPerRoute);

            let dataPie = [];

            dataPie.push(totalInboundRoute);
            dataPie.push(totalWarehouseRoute);
            dataPie.push(totalReinboundRoute);
            dataPie.push(totalDispatchRoute);
            dataPie.push(totalFailedRoute);
            dataPie.push(totalDeliveryRoute);

            setListDataPie(dataPie);

            let totalDispatchTeam = 0;
            let totalFailedTeam = 0;
            let totalDeliveryTeam = 0;

            response.dataPerTeams.forEach(element => {

                totalDispatchTeam += element.total_dispatch;
                totalFailedTeam += element.total_failed;
                totalDeliveryTeam += element.total_delivery;
            });

            let totalPackagesTeam = {
                                dispatch: totalDispatchTeam,
                                failed: totalFailedTeam,
                                delivery: totalDeliveryTeam
                            };

            setListPackageTeamTotal(totalPackagesTeam);
            setListDataPerTeam(response.dataPerTeams);
            setIsLoading(false);
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
                <td className='text-end'>{ item.total_warehouse }</td>
                <td className='text-end'>{ item.total_middle }</td>
                <td className='text-end'>{ item.total_pending }</td>
                <td className='text-end'>{ item.total_dispatch }</td>
                <td className='text-end' style={ {display: 'none'} }>{ item.total_failed }</td>
                <td className='text-end' style={ {display: 'none'} }>{ item.total_delivery }</td>
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
                <td className='text-end' style={ {display: 'none'} }>{ item.total_reinbound }</td>
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
                '#ffc107',//dispatch
                '#38D9A1',//re-inbound
                '#ffc107',//dispatch
                '#dc3545',//failed
                '#00c0ef'//delivery
              ],
            }],
            labels: [
              'Inbound',
              'Warehouse',
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
                                            <label className="form">Start date:</label>
                                        </div>
                                        <div className="col-lg-12">
                                            <input type="date" className='form-control' value={ dateStart } onChange={ (e) => setDateStart(e.target.value) }/>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-lg-2">
                                    <div className="row">
                                        <div className="col-lg-12">
                                            <label className="form">End date:</label>
                                        </div>
                                        <div className="col-lg-12">
                                            <input type="date" className='form-control' value={ dateEnd } onChange={ (e) => setDateEnd(e.target.value) }/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row justify-content-center">
                                <div className="col-lg-4">
                                    <table className=" table-condensed" style={ {width: '100%'} }>
                                        <tr>
                                            <td colspan="5">
                                                <div className='col-lg-12 text-center'> <h6> Date between : {moment(dateStart).subtract(1,'days').format('LL')} And {moment(dateEnd).subtract(1,'days').format('LL')}</h6></div><br/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div className="card text-white bg-primary mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-primary text-white text-start">  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Manifest</div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityManifest }</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-primary text-white" href="/package-manifest">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="card text-white bg-success mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-success text-white text-start">  <i className="bx bx-barcode-reader" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Inbound </div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityInbound}</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-success text-white" href="/package-inbound">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div className="col-lg-8">
                                    <table className=" table-condensed" style={ {width: '100%'} }>
                                        <tr>
                                            <td colspan="5">
                                                <div className="row">
                                                    <div className="col-lg-4"></div>
                                                    <div className='col-lg-4 text-center'> <h6> Date between : {moment(dateStart).format('LL')} And {moment(dateEnd).format('LL')}</h6><br/></div>
                                                    <div className="col-lg-4"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div className="card text-white bg-secondary mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-secondary text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> ReInbound</div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityReInbound }</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-secondary text-white" href="/package/return">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="card text-white bg-warning mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-warning text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Dispatch</div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityDispatch}</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-warning text-white" href="/package-dispatch">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="card text-white bg-danger mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-danger text-white text-start">  <i className="bx bxs-error-alt" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Failed</div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityFailed}</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-danger text-white" href="/package-dispatch">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="card text-white bg-info mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header bg-info text-white text-start">  <i className="bx bx-car" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: ''} }></i> Delivery</div>
                                                    <div className="card-body">
                                                        <h3 className=" text-white text-start">{ quantityDelivery }</h3>
                                                    </div>
                                                    <a className="card-footer text-end bg-info text-white" href="#">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="card text-white mb-3" style={{maxWidth: '18rem'}} >
                                                    <div className="card-header  text-white text-start" style={{background:'#5b0672'}}>  <i className="bx bx-box" style={ {fontSize: '16px',fontFamily: 'sans-serif',borderColor: '',background:'#5b0672'} }></i> Warehouse</div>
                                                    <div className="card-body" style={{background:'#5b0672'}}>
                                                        <h3 className=" text-white text-start">{ quantityWarehouse }</h3>
                                                    </div>
                                                    <a className="card-footer text-end text-white" style={{background:'#5b0672'}} href="/package-warehouse">
                                                        More info <i className='bi bi-arrow-right-circle'></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
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
                                <div className="row">
                                    <div className="col-lg-4">
                                        REPORT PER DATE <span>{valueCalendar.format('LL')}</span>
                                    </div>
                                    <div className="col-lg-2 form-group">
                                        <DownloadTableExcel
                                            filename="Report Mass Query"
                                            sheet="users"
                                            currentTableRef={tableRef.current}
                                        >
                                            <button className="btn btn-success btn-sm form-control">
                                                <i className="ri-file-excel-fill"></i> EXPORT
                                            </button>
                                        </DownloadTableExcel>
                                    </div>
                                </div>
                            </div>
                            <div className='row justify-content-center '>
                                <div className='col-lg-12 col-sm-12'>
                                    <div className="row">
                                        <div className="col-lg-2">
                                            <div className="row">
                                                <div className="col-lg-12">
                                                    <label className="form">Start date:</label>
                                                </div>
                                                <div className="col-lg-12">
                                                    <input type="date" className='form-control' value={ dateStartTable } onChange={ (e) => setDateStartTable(e.target.value) }/>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-lg-2">
                                            <div className="row">
                                                <div className="col-lg-12">
                                                    <label className="form">End date:</label>
                                                </div>
                                                <div className="col-lg-12">
                                                    <input type="date" className='form-control' value={ dateEndTable } onChange={ (e) => setDateEndTable(e.target.value) }/>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-lg-2" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                            {
                                                (
                                                    isLoading
                                                    ? 
                                                        <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                    :
                                                        ''
                                                )
                                            }
                                        </div>
                                        <div className="col-lg-12" style={ {display: 'none'} }>

                                            <LocalizationProvider dateAdapter={AdapterDayjs}>
                                                <Grid item xs={12} md={6}>
                                                    <CalendarPicker date={valueCalendar} onChange={(newDate) => setValueCalendar(newDate)} />
                                                </Grid>
                                            </LocalizationProvider>
                                        </div>
                                    </div>
                                    <div className='row'>
                                        <div className='col-12 col-sm-12 mt-2' style={ {display: 'none'} }>
                                            <h6 className="card-title"> <span>CHART PER DAY </span></h6>
                                            <canvas className="chart w-100" id="pieChart" style={ {display: 'none'} }></canvas>
                                        </div>
                                   </div>
                                </div>
                                <div className='col-lg-6 mt-2' style={ {display: 'none'} }>
                                    <h6 className="card-title "> <span>DATA TABLE PER DAY - TEAM</span></h6>
                                    <div className="row form-group table-responsive">
                                        <div className="col-lg-12">
                                            <table className="table table-hover table-condensed table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style={{backgroundColor: '#fff',color: '#000'}}>#</th>
                                                        <th style={{backgroundColor: '#fff',color: '#000'}}>TEAM</th>
                                                        <th style={{backgroundColor: '#38D9A1',color: '#fff', display: 'none'} }>RE-INBOUND</th>
                                                        <th className='bg-warning'>DISPATCH</th>
                                                        <th className='bg-danger'>FAILED</th>
                                                        <th className='bg-info'>DELIVERY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style={{backgroundColor: '#D3F7E2',color: '#000'}}>
                                                        <td></td>
                                                        <td><b>   TOTAL:</b></td>
                                                        <td className='text-end' style={ {display: 'none'} }><b>{listPackageTeamTotal.reinbound}</b></td>
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
                                <div className='col-lg-12 col-sm-12 mt-2'>
                                    <h6 className="card-title "> <span>DATA TABLE PER DAY - ROUTE</span></h6>
                                    <div className="row form-group table-responsive">
                                        <div className="col-lg-12">
                                            <table ref={ tableRef } className="table table-hover table-condensed table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style={{backgroundColor: '#fff',color: '#000'}}>#</th>
                                                        <th style={{backgroundColor: '#fff',color: '#000'}}>ROUTE</th>
                                                        <th className='bg-success'>INBOUND</th>
                                                        <th className='bg-warning'>WAREHOUSE</th>
                                                        <th className='bg-info'>MIDDLE MILE SCAN</th>
                                                        <th style={{backgroundColor: '#38D9A1',color: '#fff' }}>TOTAL PENDING DISPATCH</th>
                                                        <th className='bg-warning'>DISPATCH</th>
                                                        <th className='bg-danger' style={ {display: 'none'} }>FAILED</th>
                                                        <th className='bg-info' style={ {display: 'none'} }>DELIVERY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style={{backgroundColor: '#D3F7E2',color: '#000'}}>
                                                        <td></td>
                                                        <td><b>   TOTAL:</b></td>
                                                        <td className='text-end'><b>{listPackageRouteTotal.inbound}</b></td>
                                                        <td className='text-end'><b>{listPackageRouteTotal.warehouse}</b></td>
                                                        <td className='text-end'><b>{listPackageRouteTotal.middlemilescan}</b></td>
                                                        <td className='text-end'><b>{listPackageRouteTotal.pending}</b></td>
                                                        <td className='text-end'><b>{listPackageRouteTotal.dispatch}</b></td>
                                                        <td className='text-end' style={ {display: 'none'} }><b>{listPackageRouteTotal.failed}</b></td>
                                                        <td className='text-end' style={ {display: 'none'} }><b>{listPackageRouteTotal.delivery}</b></td>
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
