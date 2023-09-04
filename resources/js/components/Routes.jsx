import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment';

function Routes() {

    const [id, setId]               = useState(0);
    const [name, setName]           = useState('');
    const [zipCode, setZipCode]     = useState('');
    const [city, setCity]           = useState('');
    const [county, setCounty]       = useState('');
    const [type, setType]           = useState('');
    const [state, setState]         = useState('');
    const [latitude, setLatitude]   = useState('');
    const [longitude, setLongitude] = useState('');

    const [listRoute, setListRoute]   = useState([]);

    const [listCity, setListCity]               = useState([]);
    const [listCounty, setListCounty]           = useState([]);
    const [listType, setListType]               = useState([]);
    const [listState, setListState]             = useState([]);
    const [listRouteSearch, setListRouteSearch] = useState([]);
    const [listLatitude, setListLatitude]       = useState([]);
    const [listLongitude, setListLongitude]     = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalRoute, setTotalRoute] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [zipCodeSearch, setZipCodeSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const [file, setFile] = useState('');

    useEffect(() => {

        listAllRoute(page, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);

    }, [zipCodeSearch])

    useEffect(() => {

        listFilter();

    }, [])

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

    const handlerChangePage = (pageNumber) => {

        listAllRoute(pageNumber, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
    }

    const listAllRoute = (pageNumber, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList) => {

        fetch(url_general +'routes/list/'+ CitySearchList +'/'+ CountySearchList +'/'+ TypeSearchList +'/'+ StateSearchList +'/'+ RouteSearchList +'/'+ LatitudeSearchList +'/'+ LongitudeSearchList +'?zipCode='+ zipCodeSearch +'&page='+ pageNumber)
        .then(res => res.json())
        .then((response) => { 

            setListRoute(response.routeList.data);
            setPage(response.routeList.current_page);
            setTotalPage(response.routeList.per_page);
            setTotalRoute(response.routeList.total);
        });
    }

    const listFilter = () => {

        fetch(url_general +'routes/filter/list')
        .then(res => res.json())
        .then((response) => { 

            setListCity(response.listCity);
            setListCounty(response.listCounty);
            setListType(response.listType);
            setListState(response.listState);
            setListRouteSearch(response.listRoute);
            setListLatitude(response.listLatitude);
            setListLongitude(response.listLongitude);

            listOptionCity(response.listCity);
            listOptionCounty(response.listCounty);
            listOptionType(response.listType);
            listOptionState(response.listState);
            listOptionRoute(response.listRoute);
            listOptionLatitude(response.listLatitude);
            listOptionLongitude(response.listLongitude);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Route')
            setTextButtonSave('Update');
        }
        else
        {
            clearForm();
            setTitleModal('Add Route')
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalRouteInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveRoute = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('zipCode', zipCode);
        formData.append('city', city);
        formData.append('county', county);
        formData.append('type', type);
        formData.append('state', state);
        formData.append('latitude', latitude);
        formData.append('longitude', longitude);
        formData.append('name', name);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'routes/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Route was recorded!", {

                            icon: "success",
                        });

                        listAllRoute(page, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
                        clearForm();
                    }
                    else(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index).style.display = 'block';
                            document.getElementById(index).innerHTML     = response.errors[index][0];
                        }
                    }

                    LoadingHide();
                },
            );
        }
        else
        {
            LoadingShow();

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'routes/update/'+ id, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json())
            .then((response) => {

                if(response.stateAction)
                {
                    listAllRoute(page, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);

                    swal("The route has been updated!", {

                        icon: "success",
                    });
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }
            });
        }
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShowMap();

        fetch(url_general +'routes/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

                if(response.stateAction)
                {
                    swal("Se importó el archivo!", {

                        icon: "success",
                    });

                    document.getElementById('fileImport').value = '';

                    listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
                    listFilter();

                    setViewButtonSave('none');
                }

                LoadingHideMap();
            },
        );
    }

    const getRoute = (id) => {

        fetch(url_general +'routes/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let route = response.route;

            setId(route.id);
            setZipCode(route.zipCode);
            setCity(route.city);
            setCounty(route.county);
            setType(route.type);
            setState(route.state);
            setLatitude(route.latitude);
            setLongitude(route.longitude);
            setName(route.name);

            handlerOpenModal(route.id);
        });
    }

    const deleteRoute = (id) => {

        swal({
            title: "You want to delete?",
            text: "The path will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'routes/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Route was successfully removed!", {

                            icon: "success",
                        });

                        listAllRoute(page);
                    }
                });
            } 
        });
    }

    const clearForm = () => {

        setId(0);
        setName('');
    }

    const clearValidation = () => { 

        document.getElementById('zipCode').style.display = 'none';
        document.getElementById('zipCode').innerHTML     = ''

        document.getElementById('city').style.display = 'none';
        document.getElementById('city').innerHTML     = ''

        document.getElementById('county').style.display = 'none';
        document.getElementById('county').innerHTML     = ''

        document.getElementById('type').style.display = 'none';
        document.getElementById('type').innerHTML     = ''

        document.getElementById('state').style.display = 'none';
        document.getElementById('state').innerHTML     = ''

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';

        document.getElementById('latitude').style.display = 'none';
        document.getElementById('latitude').innerHTML     = ''

        document.getElementById('longitude').style.display = 'none';
        document.getElementById('longitude').innerHTML     = ''
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }


    const listCountyOption = listCounty.map( (county, i) => {

        return (

            <option value={ county.county }>{ county.county }</option>
        );
    });

    const [optionsCitySearch, setOptionsCitySearch]           = useState([]);
    const [optionsCountySearch, setOptionsCountySearch]       = useState([]);
    const [optionsTypeSearch, setOptionsTypeSearch]           = useState([]);
    const [optionsStateSearch, setOptionsStateSearch]         = useState([]);
    const [optionsRouteSearch, setOptionsRouteSearch]         = useState([]);
    const [optionsLatitudeSearch, setOptionsLatitudeSearch]   = useState([]);
    const [optionsLongitudeSearch, setOptionsLongitudeSearch] = useState([]);

    const listOptionCity = (listCity) => {

        setOptionsCitySearch([]);

        listCity.map( (city, i) => {

            optionsCitySearch.push({ value: city.city, label: city.city });

            setOptionsCitySearch(optionsCitySearch);
        });
    }

    const listOptionCounty = (listCounty) => {

        setOptionsCountySearch([]);

        listCounty.map( (county, i) => {

            optionsCountySearch.push({ value: county.county, label: county.county });

            setOptionsCountySearch(optionsCountySearch);
        });
    }

    const listOptionType = (listType) => {

        setOptionsTypeSearch([]);

        listType.map( (type, i) => {

            optionsTypeSearch.push({ value: type.type, label: type.type });

            setOptionsTypeSearch(optionsTypeSearch);
        });
    }

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.state, label: state.state });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const listOptionRoute = (listRoute) => {

        setOptionsRouteSearch([]);

        listRoute.map( (route, i) => {

            optionsRouteSearch.push({ value: route.name, label: route.name });

            setOptionsRouteSearch(optionsRouteSearch);
        });
    }

    const listOptionLatitude = (listLatitude) => {

        setOptionsLatitudeSearch([]);

        listLatitude.map( (latitude, i) => {

            optionsLatitudeSearch.push({ value: latitude.latitude, label: latitude.latitude });

            setOptionsLatitudeSearch(optionsLatitudeSearch);
        });
    }

    const listOptionLongitude = (listLongitude) => {

        setOptionsLongitudeSearch([]);

        listLongitude.map( (longitude, i) => {

            optionsLongitudeSearch.push({ value: longitude.longitude, label: longitude.longitude });

            setOptionsLongitudeSearch(optionsLongitudeSearch);
        });
    }


    const [CitySearchList, setCitySearchList]           = useState('all');
    const [CountySearchList, setCountySearchList]       = useState('all');
    const [TypeSearchList, setTypeSearchList]           = useState('all');
    const [StateSearchList, setStateSearchList]         = useState('all');
    const [RouteSearchList, setRouteSearchList]         = useState('all');
    const [LatitudeSearchList, setLatitudeSearchList]   = useState('all');
    const [LongitudeSearchList, setLongitudeSearchList] = useState('all');

    const handlerChangeCity = (cities) => {

        if(cities.length != 0)
        {
            let citiesSearch = '';

            cities.map( (route) => {

                citiesSearch = citiesSearch == '' ? route.value : citiesSearch +','+ route.value;
            });

            setCitySearchList(citiesSearch);

            listAllRoute(1, citiesSearch, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
        else
        {
            setCitySearchList('all');

            listAllRoute(1, 'all', CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
    }

    const handlerChangeCounty = (countys) => {

        if(countys.length != 0)
        {
            let countiesSearch = '';

            countys.map( (route) => {

                countiesSearch = countiesSearch == '' ? route.value : countiesSearch +','+ route.value;
            });

            setCountySearchList(countiesSearch);

            listAllRoute(1, CitySearchList, countiesSearch, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
        else
        {
            setCountySearchList('all');

            listAllRoute(1, CitySearchList, 'all', TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
    }

    const handlerChangeType = (types) => {

        if(types.length != 0)
        {
            let typesSearch = '';

            types.map( (route) => {

                typesSearch = typesSearch == '' ? route.value : typesSearch +','+ route.value;
            });

            setTypeSearchList(typesSearch);

            listAllRoute(1, CitySearchList, CountySearchList, typesSearch, StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
        else
        {
            setTypeSearchList('all');

            listAllRoute(1, CitySearchList, CountySearchList, 'all', StateSearchList, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (route) => {

                statesSearch = statesSearch == '' ? route.value : statesSearch +','+ route.value;
            });

            setStateSearchList(statesSearch);

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, statesSearch, RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
        else
        {
            setStateSearchList('all');

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, 'all', RouteSearchList, LatitudeSearchList, LongitudeSearchList);
        }
    }

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearchList(routesSearch);

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, routesSearch, LatitudeSearchList, LongitudeSearchList);
        }
        else
        {
            setRouteSearchList('all');

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, 'all', LongitudeSearchList, LongitudeSearchList);
        }
    }

    const handlerChangeLatitude = (latitudes) => {

        if(latitudes.length != 0)
        {
            let latitudesSearch = '';

            latitudes.map( (latitude) => {

                latitudesSearch = latitudesSearch == '' ? latitude.value : latitudesSearch +','+ latitude.value;
            });

            setLatitudeSearchList(latitudesSearch);

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, latitudesSearch, LongitudeSearchList);
        }
        else
        {
            setLatitudeSearchList('all');

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, 'all', LongitudeSearchList);
        }
    }

    const handlerChangeLongitude = (longitudes) => {

        if(longitudes.length != 0)
        {
            let longitudesSearch = '';

            longitudes.map( (route) => {

                longitudesSearch = longitudesSearch == '' ? route.value : longitudesSearch +','+ route.value;
            });

            setLongitudeSearchList(longitudesSearch);

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, longitudesSearch);
        }
        else
        {
            setLongitudeSearchList('all');

            listAllRoute(1, CitySearchList, CountySearchList, TypeSearchList, StateSearchList, RouteSearchList, LatitudeSearchList, 'all');
        }
    }

    const listRouteTable = listRoute.map( (route, i) => {

        return (

            <tr key={i}>
                <td>{ route.zipCode }</td>
                <td>{ route.city }</td>
                <td>{ route.county }</td>
                <td>{ route.type }</td>
                <td>{ route.state }</td>
                <td>{ route.name }</td>
                <td>{ route.latitude }</td>
                <td>{ route.longitude }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getRoute(route.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    {
                        route.teams.length == 0 
                        ?
                            <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteRoute(route.id) }>
                                <i className="bx bxs-trash-alt"></i>
                            </button>
                        :
                            ''
                    }
                </td>
            </tr>
        );
    });

    const modalRouteInsert = <React.Fragment>
                                    <div className="modal fade" id="modalRouteInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveRoute }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label>Zip Code</label>
                                                                <div id="zipCode" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ zipCode } maxLength="100" onChange={ (e) => setZipCode(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>City</label>
                                                                <div id="city" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ city } maxLength="100" onChange={ (e) => setCity(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label>County</label>
                                                                <div id="county" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ county } maxLength="100" onChange={ (e) => setCounty(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Type</label>
                                                                <div id="type" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ type } maxLength="100" onChange={ (e) => setType(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label>State</label>
                                                                <div id="state" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ state } maxLength="100" onChange={ (e) => setState(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Route</label>
                                                                <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ name } maxLength="100" onChange={ (e) => setName(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label>Latitude</label>
                                                                <div id="latitude" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ latitude } maxLength="100" onChange={ (e) => setLatitude(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Longitude</label>
                                                                <div id="longitude" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ longitude } maxLength="100" onChange={ (e) => setLongitude(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary">{ textButtonSave }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalRouteInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <button type="button" className="btn btn-primary btn-sm form-control" onClick={ () => onBtnClickFile() }>
                                                    IMPORT CSV
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary btn-sm form-control" onClick={ () => handlerImport() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div style={ {display: 'none'} } className="col-lg-1">
                                        <button className="btn btn-success btn-sm pull-right form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group" style={ {display: 'none'} }>
                                <div className="col-lg-12"> 
                                    <input type="text" value={ zipCodeSearch } onChange={ (e) => setZipCodeSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12 table-responsive">
                                    <table className="table table-hover table-condensed">
                                        <tr>
                                            <td>
                                                <div className="col-lg-12"> 
                                                    <input type="text" value={ zipCodeSearch } onChange={ (e) => setZipCodeSearch(e.target.value) } className="form-control" placeholder="Zip Code..."/>
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeCity(e) } options={ optionsCitySearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeCounty(e) } options={ optionsCountySearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeType(e) } options={ optionsTypeSearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRouteSearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeLatitude(e) } options={ optionsLatitudeSearch } />
                                                </div>
                                            </td>
                                            <td> 
                                                <div className="col-lg-12">
                                                    <Select isMulti onChange={ (e) => handlerChangeLongitude(e) } options={ optionsLongitudeSearch } />
                                                </div>
                                            </td>
                                        </tr>
                                        <thead>
                                            <tr>
                                                <th>ZIP CODE</th>
                                                <th>CITY</th>
                                                <th>COUNTY</th>
                                                <th>TYPE</th>
                                                <th>STATE</th>
                                                <th>ROUTE</th>
                                                <th>LATITUDE</th>
                                                <th>LONGITUDE</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listRouteTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-12">
                    <Pagination
                        activePage={page}
                        totalItemsCount={totalRoute}
                        itemsCountPerPage={totalPage}
                        onChange={(pageNumber) => handlerChangePage(pageNumber)}
                        itemClass="page-item"
                        linkClass="page-link"
                        firstPageText="First"
                        lastPageText="Last"
                    />
                </div>
            </div>
        </section>
    );
}

export default Routes;

// DOM element
if (document.getElementById('routes')) {
    ReactDOM.render(<Routes />, document.getElementById('routes'));
}