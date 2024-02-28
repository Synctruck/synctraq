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
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate]     = useState('');
    const [typeRange, setTypeRange] = useState('1');

    const [listTeam, setListTeam] = useState([]);
    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [listDriver, setListDriver] = useState([]);

    const [totalTasks, setTotalTasks] = useState(0);

    const [quantityDeliveriesView, setQuantityDeliveriesView] = useState(0);
    const [quantityFailedsView, setQuantityFailedsView] = useState(0);

    const [quantityDeliveriesViewPercentage, setQuantityDeliveriesViewPercentage] = useState(0);
    const [quantityFailedsViewPercentage, setQuantityFailedsViewPercentage] = useState(0);

    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        listAllTeam();
    }, []);

    useEffect(() => {

        getDeliveries(typeRange);
        return () => {}
    }, [startDate, endDate, idTeam, idDriver]);

    const getDeliveries = async (rangeType) => {
        setIsLoading(true);

        if(rangeType != 'custom')
        {
            await fetch(`${url_general}package-deliveries-dashboard/${rangeType}/${idTeam}/${idDriver}`)
            .then(res => res.json())
            .then((response) => {

                setIsLoading(false);

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


                let quantityDeliveries = (dataDeliveriesList.length > 0 ?  dataDeliveriesList.reduce((a, b) => a + b, 0) : 0);
                let quantityFaileds    = (dataFailedsList.length > 0 ? dataFailedsList.reduce((a, b) => a + b, 0) : 0);

                graphOne(response, dataDeliveriesList, dataFailedsList);
                graphPie(quantityDeliveries, quantityFaileds);
            });
        }
        else
        {
            if(startDate != '' && endDate != '')
            {
                let date1      = moment(startDate);
                let date2      = moment(endDate);
                let difference = date2.diff(date1,'days');

                if(difference <= 7)
                {
                    await fetch(`${url_general}package-deliveries-dashboard/${startDate}/${endDate}/${idTeam}/${idDriver}`)
                    .then(res => res.json())
                    .then((response) => {

                        setIsLoading(false);

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


                        let quantityDeliveries = (dataDeliveriesList.length > 0 ?  dataDeliveriesList.reduce((a, b) => a + b, 0) : 0);
                        let quantityFaileds    = (dataFailedsList.length > 0 ? dataFailedsList.reduce((a, b) => a + b, 0) : 0);

                        graphOne(response, dataDeliveriesList, dataFailedsList);
                        graphPie(quantityDeliveries, quantityFaileds);
                    });
                }
                else
                {
                    swal(`Maximum limit to export is 7 days`, {
                        icon: "warning",
                    });
                }
            }
        }
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
                name: 'Delivered',
                data: dataDeliveriesList
            }, {
                name: 'Failed',
                data: dataFailedsList
            }]
        });
    }

    const graphPie = (quantityDeliveries, quantityFaileds) => {
        let totalQuantity = parseInt(quantityDeliveries) + parseInt(quantityFaileds);
        let percentageDeliveries = (quantityDeliveries / totalQuantity) * 100;
        let percentageFaileds = (quantityFaileds / totalQuantity) * 100;

        setQuantityDeliveriesView(quantityDeliveries);
        setQuantityFailedsView(quantityFaileds);

        setTotalTasks(totalQuantity);

        setQuantityDeliveriesViewPercentage(percentageDeliveries.toFixed(2));
        setQuantityFailedsViewPercentage(percentageFaileds.toFixed(2));

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
                    ['', quantityDeliveries],
                    ['', quantityFaileds],
                ]
            }]
        });
    }

    const handlerChangeRangeType = (rangeType) => {
        setViewRangeDates(false);
        setTypeRange(rangeType);

        if(rangeType != 'custom')
        {
            getDeliveries(rangeType);
        }
        else
        {
            getDeliveries(rangeType);
            setViewRangeDates(true);
        }
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

    const [viewRangeDates, setViewRangeDates] = useState(false);

    return (
        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body" >
                            <div className="row mb-4">
                                <div className="col-lg-7 text-center">
                                    <h4>Completed Tasks</h4>
                                    {
                                        (
                                            isLoading
                                            ?
                                                <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                            :
                                                <figure class="highcharts-figure">
                                                    <div id="container"></div>
                                                </figure>
                                        )
                                    }
                                </div>
                                <div className="col-lg-5">
                                    <div className="row">
                                        <div className="col-lg-12">
                                            <table className="table table-condensed">
                                                <tr>
                                                   <td><b>Date Range</b></td>
                                                   <td>
                                                       <select className="form-control" onChange={ (e) => handlerChangeRangeType(e.target.value)}>
                                                           <option value="1">Last 24 hrs</option>
                                                           <option value="7">Last Week</option>
                                                           <option value="custom">Custom</option>
                                                       </select>
                                                   </td>
                                                </tr>
                                                {
                                                    viewRangeDates
                                                    ?
                                                        <>
                                                            <tr>
                                                               <td></td>
                                                               <td>
                                                                    <input type="date" value={ startDate } onChange={ (e) => setStartDate(e.target.value) }/>
                                                                    <input type="date" value={ endDate } onChange={ (e) => setEndDate(e.target.value) }/>
                                                               </td>
                                                            </tr>
                                                        </>
                                                    :
                                                        ''
                                                }
                                                <tr>
                                                   <td><b>Team</b></td>
                                                   <td>
                                                        <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                            <option value="">All</option>
                                                            { listTeamSelect }
                                                        </select>
                                                   </td>
                                                </tr>
                                                <tr>
                                                   <td><b>Driver</b></td>
                                                   <td>
                                                       <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                            <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                   </td>
                                                </tr>
                                            </table>
                                        </div>
                                        {
                                            (
                                                isLoading
                                                ?
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <>
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
                                                                        <button className="btn btn-success form-control">Delivered</button>
                                                                    </td>
                                                                    <td>{ quantityDeliveriesView } ({ quantityDeliveriesViewPercentage }%)</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <button className="btn btn-danger form-control" style={ {backgroundColor: '#dc3545'} }>Failed</button>
                                                                    </td>
                                                                    <td>{ quantityFailedsView } ({ quantityFailedsViewPercentage }%)</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Total</td>
                                                                    <td>{ totalTasks } Tasks</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </>
                                            )
                                        }
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
