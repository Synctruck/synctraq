import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function Charge() {

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

        fetch(url_general +'charge-company/list/'+ dateInit +'/'+ dateEnd +'/'+ idCompany  +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.chargeList.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.chargeList.total);
            setTotalPage(response.chargeList.per_page);
            setPage(response.chargeList.current_page);
            setQuantityDelivery(response.chargeList.total);

            setRoleUser(response.roleUser);
            setListState(response.listState);

            setButtonDisplay(response.chargeCompany);

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

    const handlerExportCharge = (id) => {

        location.href = url_general +'charge-company/export/'+ id;
    }

    const listReportTable = listReport.map( (charge, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { charge.created_at.substring(5, 7) }-{ charge.created_at.substring(8, 10) }-{ charge.created_at.substring(0, 4) }
                </td>
                <td>
                    { charge.created_at.substring(11, 19) }
                </td>
                <td><b>{ charge.company.name }</b></td>
                <td>{ charge.startDate }</td>
                <td>{ charge.endDate }</td>
                <td className="text-success text-right"><b>{ charge.total +' $' }</b></td>
                <td>
                    <button className="btn btn-primary form-control" onClick={ () => handlerExportCharge(charge.id) }>
                        <i className="ri-file-excel-fill"></i> Export
                    </button>
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

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
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
                                                <th><b>DATE</b></th>
                                                <th><b>HOUR</b></th>
                                                <th><b>COMPANY</b></th>
                                                <th><b>START DATE</b></th>
                                                <th><b>END DATE</b></th>
                                                <th><b>TOTAL</b></th>
                                                <th><b>ACTION</b></th>
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

export default Charge;

if (document.getElementById('charge'))
{
    ReactDOM.render(<Charge />, document.getElementById('charge'));
}