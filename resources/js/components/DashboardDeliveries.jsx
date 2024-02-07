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

    /*useEffect(() => {
        getDataPerDate();
    },[]);*/

    useEffect(() => {
        
        getDeliveries(typeRange);
        return () => {}
    }, [dateStart, dateEnd]);


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

                if(response.dataSQLDeliveries[0]['total'+ index] == null)
                {
                    dataFailedsList.push(0);
                }
                else
                {
                    dataFailedsList.push(response.dataSQLDeliveries[0]['total'+ index]);
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
            series: [{
                name: 'Deliveries',
                data: dataDeliveriesList
            }, {
                name: 'Faileds',
                data: dataFailedsList
            }]
        });
    }

    const handlerChangeRangeType = (rangeType) => {
        getDeliveries(rangeType);
    }

    return (
        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body" >
                            <div className="row mb-4">
                                <div className="col-lg-8 text-center">
                                    <h4>Completed Tasks</h4>
                                    <figure class="highcharts-figure">
                                        <div id="container"></div>
                                    </figure>
                                </div>
                                <div className="col-lg-4">
                                    <table className="table table-condensed">
                                        <tr>
                                           <td>Date Range</td>
                                           <td>
                                               <select className="form-control" onChange={ (e) => handlerChangeRangeType(e.target.value)}>
                                                   <option value="1">Last 24 hrs</option>
                                                   <option value="7">Last Week</option>
                                                   <option value="30">Last Month</option>
                                               </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td>Team</td>
                                           <td>
                                               <select name="" id="" className="form-control">
                                                   <option value="24h">Last 24 hrs</option>
                                                   <option value="7d">Last Week</option>
                                                   <option value="1month">Last Month</option>
                                               </select>
                                           </td>
                                        </tr>
                                        <tr>
                                           <td>Driver</td>
                                           <td>
                                               <select className="form-control" onChange={ (range) => handlerChangeRangeType(range)}>
                                                   <option value="24h">Last 24 hrs</option>
                                                   <option value="7d">Last Week</option>
                                                   <option value="1month">Last Month</option>
                                               </select>
                                           </td>
                                        </tr>
                                    </table>
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
