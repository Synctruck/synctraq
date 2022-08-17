import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Dashboard() {

    const [quantityManifest, setQuantityManifest]   = useState(0);
    const [quantityInbound, setQuantityInbound]     = useState(0);
    const [quantityNotExists, setQuantityNotExists] = useState(0);
    const [quantityDispatch, setQuantityDispatch]   = useState(0);
    const [quantityReturn, setQuantityReturn]       = useState(0);
    const [quantityDelivery, setQuantityDelivery]   = useState(0);

    const [quantityManifestToday, setQuantityManifestToday]   = useState(0);
    const [quantityInboundToday, setQuantityInboundToday]     = useState(0);
    const [quantityNotExistsToday, setQuantityNotExistsToday] = useState(0);
    const [quantityDispatchToday, setQuantityDispatchToday]   = useState(0);
    const [quantityReturnToday, setQuantityReturnToday]       = useState(0);
    const [quantityDeliveryToday, setQuantityDeliveryToday]   = useState(0);

    const [listQuantityRoute, setListQuantityRoute] = useState([]);

    const [textLoading, setTextLoading] = useState('Loading...');

    const [loading, setLoading] = useState('block');
    const [card, setCart] = useState('none');

    useEffect(() => {

        getAllQuantityStatusPackage();

    }, []);

    const getAllQuantityStatusPackage = () => {

        setLoading('block');
        setCart('none');

        fetch(url_general +'dashboard/getallquantity')
        .then(res => res.json())
        .then((response) => {

            setQuantityManifest(response.quantityManifest);
            setQuantityInbound(response.quantityInbound);
            setQuantityNotExists(response.quantityNotExists);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityReturn(response.quantityReturn);
            setQuantityDelivery(response.quantityDelivery);

            setQuantityManifestToday(response.quantityManifestToday);
            setQuantityInboundToday(response.quantityInboundToday);
            setQuantityNotExistsToday(response.quantityNotExistsToday);
            setQuantityDispatchToday(response.quantityDispatchToday);
            setQuantityReturnToday(response.quantityReturnToday);
            setQuantityDeliveryToday(response.quantityDeliveryToday);

            GraficMovementsToday(response.quantityManifestToday, response.quantityInboundToday, response.quantityNotExistsToday, response.quantityDispatchToday, response.quantityReturnToday, response.quantityDeliveryToday);
            GraficMovementsOfTheWeek();

            setLoading('none');
            setCart('block');
        });
    }

    const GraficMovementsToday = (quantityManifestToday, quantityInboundToday, quantityNotExistsToday, quantityDispatchToday, quantityReturnToday, quantityDeliveryToday) => {

        Highcharts.chart('graficMovementsToday', {
            chart: {
                type: 'column'
            },
            title: {
                align: 'left',
                text: 'Movements of the day 23/05/2022'
            },
            accessibility: {
                announceNewData: {
                    enabled: true
                }
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                title: {
                    text: ''
                }

            },
            legend: {
                enabled: false
            },
            plotOptions: {
                series: {
                    borderWidth: 0,
                    dataLabels: {
                        enabled: true,
                        format: '{point.y:.1f}'
                    }
                }
            },

            tooltip: {
                headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.2f} </b> of total<br/>'
            },

            series: [
                {
                    name: "Browsers",
                    colorByPoint: true,
                    data: [
                        {
                            name: "Manifest",
                            y: quantityManifestToday,
                            drilldown: "Manifest"
                        },
                        {
                            name: "Inbound",
                            y: quantityInboundToday,
                            drilldown: "Inbound"
                        },
                        {
                            name: "Not Exists",
                            y: quantityNotExistsToday,
                            drilldown: "Not Exists"
                        },
                        {
                            name: "Dispatch",
                            y: quantityDispatchToday,
                            drilldown: "Dispatch"
                        },
                        {
                            name: "Returns",
                            y: quantityReturnToday,
                            drilldown: "Returns"
                        },
                        {
                            name: "Delivery",
                            y: quantityDeliveryToday,
                            drilldown: "Delivery"
                        },
                    ]
                }
            ],
            drilldown: {
                breadcrumbs: {
                    position: {
                        align: 'right'
                    }
                },
            }
        });
    }

    const GraficMovementsOfTheWeek = () => {

        Highcharts.chart('graficMovementsOfTheWeek', {
            chart: {
                type: 'areaspline'
            },
            title: {
                text: 'Movements of the week (23/05/2022 - 29/05/2022)'
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                verticalAlign: 'top',
                x: 150,
                y: 100,
                floating: true,
                borderWidth: 1,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF'
            },
            xAxis: {
                categories: [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday',
                    'Sunday'
                ],
                plotBands: [{ // visualize the weekend
                    from: 4.5,
                    to: 6.5,
                    color: 'rgba(68, 170, 213, .2)'
                }]
            },
            yAxis: {
                title: {
                    text: 'Fruit units'
                }
            },
            tooltip: {
                shared: true,
                valueSuffix: ' units'
            },
            credits: {
                enabled: false
            },
            plotOptions: {
                areaspline: {
                    fillOpacity: 0.5
                }
            },
            series: [{
                name: 'Manifest',
                data: [3, 4, 3, 5, 4, 10, 12]
            }, {
                name: 'Inbound',
                data: [1, 3, 4, 3, 3, 5, 4]
            }]
        });
    }

    return (

        <section className="section">
            <div className="row"> 
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body" style={ {display: loading} }>
                            <div class="col-lg-12 text-center form-group">
                                <h5 class="card-title">{ textLoading }</h5>
                            </div>
                        </div>
                        <div className="card-body" style={ {display: card} }>
                            <div className="row form-group table-responsive">
                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-primary d-flex align-items-center justify-content-center">
                                                        <i class="bx bxs-box" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Manifest</h5>
                                                    <h4>{ quantityManifest }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-success d-flex align-items-center justify-content-center">
                                                        <i class="bx bx-barcode-reader" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Inbound</h5>
                                                    <h4>{ quantityInbound }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-danger d-flex align-items-center justify-content-center">
                                                        <i class="bx bx-barcode-reader" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Not Exists</h5>
                                                    <h4>{ quantityNotExists }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-warning d-flex align-items-center justify-content-center">
                                                        <i class="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Dispatch</h5>
                                                    <h4>{ quantityDispatch }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-danger d-flex align-items-center justify-content-center">
                                                        <i class="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Returns</h5>
                                                    <h4>{ quantityReturn }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 text-center form-group">
                                    <div className="cardDashboard">
                                        <div class="card-body">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div class="card-icon rounded-circle-dashboard rounded-circle-success d-flex align-items-center justify-content-center">
                                                        <i class="bx bx-car" style={ {fontSize: '30px'} }></i>
                                                    </div>
                                                </div>
                                                <div className="col-lg-8">
                                                    <h5 class="card-title">Delivery</h5>
                                                    <h4>{ quantityDelivery }</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr/>
                            <div className="row form-group" style={ {display: 'none'} }>
                                <div class="col-lg-2 text-center">
                                    <div class="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div class="card-body">
                                            <h5 class="card-title">FEDEX  <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <div class="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div class="card-body">
                                            <h5 class="card-title">UPS   <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <div class="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.125rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div class="card-body">
                                            <h5 class="card-title">DHL   <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <div class="card info-card sales-card alert-danger" style={ {background: 'white', borderRadius: '0.5rem', boxShadow: '0 0.1rem 0.25rem rgb(0 0 0 / 5%)'} }>
                                        <div class="card-body">
                                            <h5 class="card-title">USPS  <span></span></h5>
                                            <div className="row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="row form-group">
                                <div id="graficMovementsToday" className="col-lg-6"></div>
                                <div id="graficMovementsOfTheWeek" className="col-lg-6" style={ {display: 'none'} }></div>
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