import React, { useState, useEffect, useRef } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'

import Pagination from "react-js-pagination"
import swal from 'sweetalert'
//mui
import dayjs from 'dayjs';
import moment from 'moment';
import ReactLoading from 'react-loading';
import { DownloadTableExcel } from 'react-export-table-to-excel';
// moment().format();

function DashboardDeliveries() {
 
    const [loading, setLoading]     = useState('block');
    const [dateStart, setDateStart] = useState(auxDateStart);
    const [dateEnd, setDateEnd]     = useState(auxDateStart);
    const [typeRange, setTypeRange] = useState('1');

    const [listTeam, setListTeam] = useState([]);
    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [listDriver, setListDriver] = useState([]);

    useEffect(() => {
        listAllTeam();
    }, []);

    useEffect(() => {
        
        getDeliveries(typeRange);
        graphPie();

        return () => {}
    }, [dateStart, dateEnd, idTeam, idDriver]);


    const getDeliveries = async (rangeType) => {
        setLoading('block');

        await fetch(`${url_general}package-deliveries-dashboard/${rangeType}`)
        .then(res => res.json())
        .then((response) => {

            let dataDeliveriesList = [];
            let dataFailedsList    = [];

            response.dataDateList.forEach((date, index) => {
                if(response.dataSQLDeliveries[0]['total'+ index] == null)
                {
                    dataDeliveriesList.push(0);
                }
                else
                {
                    dataDeliveriesList.push(response.dataSQLDeliveries[0]['total'+ index]);
                }

                if(response.dataSQLFaileds[0]['total'+ index] == null)
                {
                    dataFailedsList.push(0);
                }
                else
                {
                    dataFailedsList.push(response.dataSQLFaileds[0]['total'+ index]);
                }
            });

            console.log(dataDeliveriesList, dataFailedsList);
            graphOne(response, dataDeliveriesList, dataFailedsList);
        });
    }

    const graphOne = (response, dataDeliveriesList, dataFailedsList) => {

        Highcharts.chart('container', {
            chart: {
                type: 'column'
            },
            title: {
                text: '',
                align: 'center'
            },
            xAxis: {
                categories: response.dataDateList
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Count trophies'
                },
                stackLabels: {
                    enabled: true
                }
            },
            legend: {
                align: 'left',
                x: 70,
                verticalAlign: 'top',
                y: 70,
                floating: true,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || 'white',
                borderColor: '#CCC',
                borderWidth: 1,
                shadow: false
            },
            tooltip: {
                headerFormat: '<b>{point.x}</b><br/>',
                pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            colors: ['#28a745', '#dc3545'],
            series: [{
                name: 'Deliveries',
                data: dataDeliveriesList
            }, {
                name: 'Faileds',
                data: dataFailedsList
            }]
        });
    }

    const graphPie = () => {
        Highcharts.chart('divPie', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: '',
                align: 'left',
                verticalAlign: 'middle',
                y: 60
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: true,
                        distance: -100,
                        style: {
                            fontWeight: 'bold',
                            color: 'white'
                        }
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '30%'],
                    size: '100%'
                }
            },
            colors: ['#28a745', '#dc3545'],
            series: [{
                type: 'pie',
                name: 'Completed Tasks',
                innerSize: '80%',
                data: [
                    ['', 73.86],
                    ['', 11.97],
                ]
            }]
        });
    }

    const handlerChangeRangeType = (rangeType) => {
        getDeliveries(rangeType);
    }

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    });

    const listAllDriverByTeam = (idTeam) => {

        setIdTeam(idTeam);
        setIdDriver(0);
        setListDriver([]);

        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.listDriver);
        });
    }

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    return (
        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body" >
                            <div className="row mb-4">
                                <div className="col-lg-7 text-center">
                                    <h4>Completed Tasks</h4>
                                    <figure class="highcharts-figure">
                                        <div id="container"></div>
                                    </figure>
                                </div>
                                <div className="col-lg-5">
                                    <div className="row">
                                        <div className="col-lg-12">
                                            <table className="table table-condensed">
                                                <tr>
                                                   <td>Date Range</td>
                                                   <td>
                                                       <select className="form-control" onChange={ (e) => handlerChangeRangeType(e.target.value)}>
                                                           <option value="1">Last 24 hrs</option>
                                                           <option value="7">Last Week</option>
                                                       </select>
                                                   </td>
                                                </tr>
                                                <tr>
                                                   <td>Team</td>
                                                   <td>
                                                        <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                            <option value="">All</option>
                                                            { listTeamSelect }
                                                        </select>
                                                   </td>
                                                </tr>
                                                <tr>
                                                   <td>Driver</td>
                                                   <td>
                                                       <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                            <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                   </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div className="col-lg-6">
                                            <div id="divPie"></div>
                                        </div>
                                        <div className="col-lg-6">
                                            <table className="table table-condensed table-bordered">
                                                <tr>
                                                    <td colspan="2"><b>Completed Tasks</b></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <button className="btn btn-success form-control">Succeeded</button>
                                                    </td>
                                                    <td>1187(99%)</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <button className="btn btn-danger form-control" style={ {backgroundColor: '#dc3545'} }>Failed</button>
                                                    </td>
                                                    <td>8(0%)</td>
                                                </tr>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>11995 Tasks</td>
                                                </tr>
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

export default DashboardDeliveries;

// DOM element
if (document.getElementById('dashboardDeliveries')) {
    ReactDOM.render(<DashboardDeliveries />, document.getElementById('dashboardDeliveries'));
}
