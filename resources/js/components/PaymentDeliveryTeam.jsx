import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function PaymentDeliveryTeam() {

    const [paymentTeam, setPaymentTeam]       = useState('null');
    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);

    const [quantityDelivery, setQuantityDelivery]   = useState(0);
    const [totalPriceCompany, setTotalPriceCompany] = useState(0);
    const [totalPriceTeam, setTotalPriceTeam]       = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [team, setTeam]         = useState('');
    const [idTeam, setIdTeam]     = useState(0);
    const [idDriver, setIdDriver] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [file, setFile]             = useState('');
    const [btnDisplay, setbtnDisplay] = useState('none');

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const inputFileRef  = React.useRef();

    useEffect(() => {

        if(String(file) == 'undefined' || file == '')
        {
            setViewButtonSave('none');
        }
        else
        {
            setViewButtonSave('block');
        }

    }, [file]);

    useEffect( () => {

        listAllTeam();
        listAllRoute();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idTeam, idDriver]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'payment-delivery/list/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setPaymentTeam(response.paymentTeam);
            setListReport(response.reportList.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.reportList.total);
            setTotalPage(response.reportList.per_page);
            setPage(response.reportList.current_page);
            setQuantityDelivery(response.reportList.total);

            setRoleUser(response.roleUser);
            setListState(response.listState);
 
            setTotalPriceCompany(parseFloat(response.totalPriceCompany).toFixed(4));
            setTotalPriceTeam(parseFloat(response.totalPriceTeam).toFixed(4));

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Administrador')
            {
                //listAllTeam();
            }
            else
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }

            setTimeout( () => {

                handlerCheckUncheckDelivery(response.reportList.data);

            }, 100);
        });
    }

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const listAllDriverByTeam = (idTeam) => {

        setListDriver([]);
        setIdTeam(idTeam);
        setIdDriver(0);

        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.listDriver);
            setTeam(response.listDriver[0].nameTeam);
        });
    }

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
        });
    }

    const handlerChangeDateInit = (date) => {

        setDateInit(date);
    }

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
    }

    const handlerChangePage = (pageNumber) => {

        listReportDispatch(pageNumber, RouteSearch, StateSearch);
    }

    const handlerCheckbox = (Reference_Number_1, checkPayment) => {

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('checkPayment', checkPayment);

        fetch(url_general +'package-delivery/insert-for-check', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                LoadingHide();
            },
        );
    }

    const handlerCheckUncheckDelivery = (listReportForCheck) => {

        listReportForCheck.map( (packageDelivery, i) => {

            if(packageDelivery.checkPayment == 1)
            {
                document.getElementById('checkCorrect'+ packageDelivery.Reference_Number_1).checked = true;
            }
            else if(packageDelivery.checkPayment == 0)
            {
                document.getElementById('checkIncorrect'+ packageDelivery.Reference_Number_1).checked = true;
            }
        });
    }

    const handlerViewDetailPrices = (detailsPrices) => {

    }

    const listReportTable = listReport.map( (packageDelivery, i) => {

        let imgs          = '';
        let urlImage      = '';
        let photoHttp     = false;
        let idsImages = packageDelivery.photoUrl.split(',');

        if(idsImages.length == 1)
        {
            imgs = <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' } width="100"/>;

            urlImage      = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png';
        }
        else if(idsImages.length >= 2)
        {
            imgs =  <>
                        <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' } width="100" style={ {border: '2px solid red'} }/>
                        <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png' } width="100" style={ {border: '2px solid red'} }/>
                    </>

            urlImage      = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' + 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png';
        }

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageDelivery.updated_at.substring(5, 7) }-{ packageDelivery.updated_at.substring(8, 10) }-{ packageDelivery.updated_at.substring(0, 4) }
                </td>
                <td>
                    { packageDelivery.updated_at.substring(11, 19) }
                </td>
                <td><b>{ packageDelivery.company }</b></td>
                <td><b>{ packageDelivery.team.name }</b></td>
                <td>{ packageDelivery.driver.name +' '+ packageDelivery.driver.nameOfOwner }</td>
                <td><b>{ packageDelivery.Reference_Number_1 }</b></td>
                <td>{ packageDelivery.Dropoff_Province }</td>
                <td>{ packageDelivery.Route }</td>
                <td><b>{ packageDelivery.pricePaymentTeam }</b></td>
            </tr>
        );
    });

    const [listViewImages, setListViewImages] = useState([]);

    const viewImages = (urlImage) => {

        setListViewImages(urlImage.split('https'));

        let myModal = new bootstrap.Modal(document.getElementById('modalViewImages'), {

            keyboard: true
        });

        myModal.show();
    }

    const listViewImagesModal = listViewImages.map( (image, i) => {

        if(i > 0)
        {
            return (

                <img src={ 'https'+ image } className="img-fluid mt-2"/>
            );
        }
    });

    const listViewPricesModal = listViewImages.map( (image, i) => {

        return (

            <img src={ 'https'+ image } className="img-fluid mt-2"/>
        );
    });

    const modalViewImages = <React.Fragment>
                                    <div className="modal fade" id="modalViewImages" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">View Images</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    { listViewImagesModal }
                                                </div>
                                                <div className="modal-footer">
                                                    <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const modalViewPackagePrices =  <React.Fragment>
                                        <div className="modal fade" id="modalViewPackagePrices" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div className="modal-dialog modal-lg">
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Data of dimensions and prices of the package</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        { listViewPricesModal }
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </React.Fragment>;

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } text={ team.name }>{ team.name }</option>
        );
    });

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listReportDispatch(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportDispatch(1, 'all', StateSearch);
        }
    };

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listReportDispatch(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportDispatch(page, RouteSearch, 'all');
        }
    };

    const [optionsStateSearch, setOptionsStateSearch] = useState([]);

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.Dropoff_Province, label: state.Dropoff_Province });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const handlerRegisterPayment = () => {

        if(quantityDelivery == 0)
        {
            swal("No packages in delivery!", {

                icon: "warning",
            });

            return 0;
        }

        if(idTeam != 0)
        {
            swal({
                title: "You want to register the payment of the TEAM: "+ team +" ?",
                text: "Start Date: "+ dateInit +' | End Date: '+ dateEnd,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((confirmation) => {

                if(confirmation)
                {
                    const formData = new FormData();

                    formData.append('idTeam', idTeam);
                    formData.append('startDate', dateInit);
                    formData.append('endDate', dateEnd);

                    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    LoadingShow(); 

                    fetch(url_general +'payment-delivery/insert', {
                        headers: { "X-CSRF-TOKEN": token },
                        method: 'post',
                        body: formData
                    })
                    .then(res => res.json()).
                    then((response) => {

                            if(response.stateAction == 'incorrectDate')
                            {
                                swal("Select a correct date range!", {

                                    icon: "error",
                                });
                            }
                            else if(response.stateAction == 'daysDifferenceIncorrect')
                            {
                                swal("Days difference incorrect!", {

                                    icon: "error",
                                });
                            }
                            else if(response.stateAction == 'paymentExists')
                            {
                                swal("There is already a payment for the selected filters!", {

                                    icon: "warning",
                                });
                            }
                            else if(response.stateAction)
                            {
                                swal("Payment was made correctly!", {

                                    icon: "success",
                                });

                                listReportDispatch(1, RouteSearch, StateSearch);
                            }

                            LoadingHide();
                        },
                    );
                }
            });
        }
        else
        {
            swal("You must select a TEAM for checkout registration!", {

                icon: "warning",
            });
        }
    }

    const handlerExportPayment = () => {

        location.href = url_general +'payment-delivery/export/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch;
    }

    return (

        <section className="section">
            { modalViewImages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-2 mb-3" style={ {display: (String(paymentTeam) == 'null' ? 'block' : 'none')} }>
                                        <button className="btn btn-success form-control" onClick={ () => handlerRegisterPayment() }>
                                            Register Payment
                                        </button>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <button className="btn btn-primary form-control" onClick={ () => handlerExportPayment() }>
                                            <i className="ri-file-excel-fill"></i> Export
                                        </button>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2">
                                        <label htmlFor="">Start date:</label>
                                        <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2">
                                        <label htmlFor="">End date:</label>
                                        <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                    </div>
                                    {
                                        roleUser == 'Administrador'
                                        ?
                                            <>
                                                <div className="col-lg-2">
                                                    <div className="form-group">
                                                        <label htmlFor="">Team</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listTeamSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                                <div className="col-lg-2">
                                                    <div className="form-group">
                                                        <label htmlFor="">Driver</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                            </>
                                        :
                                            ''
                                    }

                                    {
                                        roleUser == 'Team'
                                        ?
                                            <>
                                                <div className="col-lg-3">
                                                    <div className="form-group">
                                                        <label htmlFor="">Driver</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                            </>
                                        :
                                            ''
                                    }

                                    <div className="col-lg-2 mb-3">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                State :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                Route :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Delivery: { quantityDelivery }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Charge Company: { totalPriceCompany +' $' }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Payment Team: { totalPriceTeam +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th>COMPANY</th>
                                                <th><b>TEAM</b></th>
                                                <th><b>DRIVER</b></th>
                                                <th>PACKAGE ID</th>
                                                <th>STATE</th>
                                                <th>ROUTE</th>
                                                <th>PRICE TEAM</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listReportTable }
                                        </tbody>
                                    </table>
                                </div>
                                <div className="col-lg-12">
                                    <Pagination
                                        activePage={page}
                                        totalItemsCount={totalPackage}
                                        itemsCountPerPage={totalPage}
                                        onChange={(pageNumber) => handlerChangePage(pageNumber)}
                                        itemClass="page-item"
                                        linkClass="page-link"
                                        firstPageText="First"
                                        lastPageText="Last"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PaymentDeliveryTeam;

if (document.getElementById('paymentDeliveryTeam'))
{
    ReactDOM.render(<PaymentDeliveryTeam />, document.getElementById('paymentDeliveryTeam'));
}
