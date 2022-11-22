import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function ChargeCompany() {

    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [listCompany , setListCompany]      = useState([]);
    const [roleUser, setRoleUser]             = useState([]);

    const [quantityDelivery, setQuantityDelivery] = useState(0);
    const [totalPriceCompany, setTotalPriceCompany]     = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit]             = useState(auxDateInit);
    const [dateEnd, setDateEnd]               = useState(auxDateInit);
    const [fuelPrice, setFuelPrice]           = useState('');
    const [fuelPercentage, setFuelPercentage] = useState('');
    const [idTeam, setIdTeam]                 = useState(0);
    const [idDriver, setIdDriver]             = useState(0);
    const [idCompany, setCompany]             = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [file, setFile]             = useState('');
    const [buttonDisplay, setButtonDisplay] = useState('update');

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

        listAllCompany();
        listAllTeam();
        listAllRoute();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [idCompany, dateInit, dateEnd, idTeam, idDriver]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'charge-company/list/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.reportList.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.reportList.total);
            setTotalPage(response.reportList.per_page);
            setPage(response.reportList.current_page);
            setQuantityDelivery(response.reportList.total);

            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(response.chargeCompany)
            {
                setFuelPercentage(response.chargeCompany.fuelPercentage);
                setButtonDisplay('download');
            }
            else
            {
                setFuelPercentage('');
                setButtonDisplay('update');
            }

            setTotalPriceCompany(parseFloat(response.totalPriceCompany).toFixed(4));

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Team')
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }

            setTimeout( () => {

                handlerCheckUncheckDelivery(response.reportList.data);

            }, 100);
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            let CustomListCompany = [{id:0,name:"All companies"},...response.companyList];
            setCompany(0);
            setListCompany(CustomListCompany);

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

    const handlerExport = () => {

        location.href = url_general +'report/export/delivery/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch;
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
                <td><b>{ packageDelivery.company }</b></td>
                <td><b>{ packageDelivery.Reference_Number_1 }</b></td>
                <td>{ packageDelivery.Dropoff_Contact_Name }</td>
                <td>{ packageDelivery.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDelivery.Dropoff_Address_Line_1 }</td>
                <td>{ packageDelivery.Dropoff_City }</td>
                <td>{ packageDelivery.Dropoff_Province }</td>
                <td>{ packageDelivery.Route }</td>
                <td><b>{ packageDelivery.pricePaymentCompany +' $' }</b></td>
                <td onClick={ () => viewImages(urlImage)} style={ {cursor: 'pointer'} }>
                    { imgs }
                </td>
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

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={ company.id }>{company.name}</option>
    })

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

        if(idCompany != 0)
        {
            let companyName = '';

            listCompany.forEach( company => {

                if(company.id == idCompany)
                {
                    companyName = company.name;
                }
            });

            swal({
                title: "You want to register the charge of the COMPANY: "+ companyName +" ?",
                text: "Start Date: "+ dateInit +' | End Date: '+ dateEnd,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((confirmation) => {

                if(confirmation)
                {
                    const formData = new FormData();

                    formData.append('idCompany', idCompany);
                    formData.append('startDate', dateInit);
                    formData.append('endDate', dateEnd);
                    formData.append('fuelPrice', fuelPrice);

                    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    LoadingShow(); 

                    fetch(url_general +'charge-company/insert', {
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
                            else if(response.stateAction == 'nullFuel')
                            {
                                swal("Enter the Fuel Price!", {

                                    icon: "warning",
                                });
                            }
                            else if(response.stateAction == 'notRangeDiesel')
                            {
                                swal("There is no percentage range for the Fuel Price!", {

                                    icon: "warning",
                                });
                            }
                            else if(response.stateAction)
                            {
                                swal("Charge was made correctly!", {

                                    icon: "success",
                                });

                                setFuelPercentage(response.fuelPercentage);

                                //listAllPackage();
                                setButtonDisplay('download');
                            }

                            LoadingHide();
                        },
                    );
                }
            });
        }
        else
        {
            swal("You must select a COMPANY to update prices!", {

                icon: "warning",
            });
        }
    }

    const handlerDownloadCharge = () => {

        if(idCompany != 0)
        {
            location.href = url_general +'charge-company/export/'+ idCompany +'/'+ dateInit +'/'+ dateEnd;
        }
        else
        {
            swal("You must select a COMPANY to export!", {

                icon: "warning",
            });
        }
    }

    return (

        <section className="section">
            { modalViewImages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row" style={ {display: 'none'} }>
                                    <div className="col-lg-2 mb-3">
                                        <button className="btn btn-success form-control" onClick={ () => handlerRegisterPayment() }>
                                            Register Payment
                                        </button>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">Start date:</label>
                                        <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">End date:</label>
                                        <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                    </div>
                                    <dvi className="col-lg-2 mb-3"> 
                                        <div className="row">
                                            <div className="col-lg-12">
                                                Company:
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                    <option value="" style={ {display: 'none'} }>Select...</option>
                                                    { optionCompany }
                                                </select>
                                            </div>
                                        </div>
                                    </dvi>
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">Fuel Price $:</label>
                                        <input type="text" value={ fuelPrice } onChange={ (e) => setFuelPrice(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">Fuel Percentaje %:</label>
                                        <input type="text" value={ fuelPercentage } onChange={ (e) => setFuelPercentage(e.target.value) } className="form-control" readOnly/>
                                    </div>
                                    <div className="col-lg-2 mb-3" style={ {display: ( buttonDisplay == 'update' ? 'block' : 'none') } }>
                                        <label htmlFor="" className="text-white">--</label>
                                        <button className="btn btn-primary form-control" onClick={ () => handlerRegisterPayment() }>Update Prices</button>
                                    </div>
                                    <div className="col-lg-2 mb-3" style={ {display: ( buttonDisplay == 'download' ? 'block' : 'none') } }>
                                        <label htmlFor="" className="text-white">--</label>
                                        <button className="btn btn-success form-control" onClick={ () => handlerDownloadCharge() }>Download Charges</button>
                                    </div>
                                    {
                                        roleUser == 'Administrador'
                                        ?
                                            <>
                                                <div className="col-lg-2" style={ {display: 'none'} }>
                                                    <div className="form-group">
                                                        <label htmlFor="">Team</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listTeamSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                                <div className="col-lg-2" style={ {display: 'none'} }>
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
                                                <div className="col-lg-3" style={ {display: 'none'} }>
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

                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
                                        <div className="row">
                                            <div className="col-lg-12">
                                                State :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
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
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Company Base Price : { totalPriceCompany +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ROUTE</th>
                                                <th>BASE PRICE</th>
                                                <th>IMAGE</th>
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

export default ChargeCompany;

if (document.getElementById('chargeCompany'))
{
    ReactDOM.render(<ChargeCompany />, document.getElementById('chargeCompany'));
}
