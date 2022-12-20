import React, { useState, useEffect, Component, Fragment } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from "react-select";

function Team() {

    const [id, setId]                                 = useState(0);
    const [idRole, setIdRole]                         = useState(0);
    const [name, setName]                             = useState('');
    const [nameOfOwner, setNameOfOwner]               = useState('');
    const [address, setAddress]                       = useState('');
    const [phone, setPhone]                           = useState('');
    const [email, setEmail]                           = useState('');
    const [status, setStatus]                         = useState('');
    const [idsRoutes, setIdsRoutes]                   = useState('');
    const [permissionDispatch, setPermissionDispatch] = useState(0);
    const [idOnfleet, setIdOnfleet]                   = useState('');

    const [disabledButton, setDisabledButton] = useState(false);

    const [listUser, setListUser]   = useState([]);
    const [listRole, setListRole]   = useState([]);
    const [listRoute, setListRoute] = useState([]);
    const [listTeamRoute, setListTeamRoute] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalTeam, setTotalTeam] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const [viewButtonSave, setViewButtonSave] = useState('none');
    const [file, setFile]             = useState('');

    const inputFileRef  = React.useRef();

    useEffect(() => { 

        listAllCompany();

    }, []);

    useEffect(() => {

        listAllTeam(page);
        listAllRoute();

    }, [textSearch])

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

        listAllTeam(pageNumber);
    }

    const listAllTeam = (pageNumber) => {

        fetch(url_general +'team/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListUser(response.userList.data);
            setPage(response.userList.current_page);
            setTotalPage(response.userList.per_page);
            setTotalTeam(response.userList.total);
        });
    }

    const listAllRole = (pageNumber) => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {
            console.log('rolesss: ',response.roleList)
            setListRole(response.roleList);
        });
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            setListCompany(response.companyList);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Team')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            listAllRoute();

            clearForm();
            setTitleModal('Add Team');
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveTeam = (e) => {

        e.preventDefault();

        let dataPrices  = [];
        let minWeight   = 0;
        let maxWeight   = 0;
        let priceWeight = 0;

        listCompany.map( company => {

            minWeight   = document.getElementById('minWeight'+ 1 + company.id).value;
            maxWeight   = document.getElementById('maxWeight'+ 1 + company.id).value;
            priceWeight = document.getElementById('priceWeight'+ 1 + company.id).value;

            dataPrices.push(company.id, minWeight, maxWeight, priceWeight);

            minWeight   = document.getElementById('minWeight'+ 2 + company.id).value;
            maxWeight   = document.getElementById('maxWeight'+ 2 + company.id).value;
            priceWeight = document.getElementById('priceWeight'+ 2 + company.id).value;

            dataPrices.push(company.id, minWeight, maxWeight, priceWeight);

            minWeight   = document.getElementById('minWeight'+ 3 + company.id).value;
            maxWeight   = document.getElementById('maxWeight'+ 3 + company.id).value;
            priceWeight = document.getElementById('priceWeight'+ 3 + company.id).value;

            dataPrices.push(company.id, minWeight, maxWeight, priceWeight);
        });

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('status', status);
        formData.append('route', RouteSearch);
        formData.append('dataPrices', dataPrices);

        clearValidation();

        

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            setDisabledButton(true);

            fetch(url_general +'team/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction == true)
                    {
                        swal("Team was registered!", {

                            icon: "success",
                        });

                        listAllTeam(1);
                        clearForm();
                    }
                    else if(response.stateAction == 'notTeamOnfleet')
                    {
                        swal("The team does not exist in Onfleet!", {

                            icon: "warning",
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

                    setDisabledButton(false);
                },
            );
        }
        else
        {
            setDisabledButton(true);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'team/update/'+ id, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction == true)
                {
                    listAllTeam(1);

                    swal("Equipment was updated!", {

                        icon: "success",
                    });
                }
                else if(response.stateAction == 'notTeamOnfleet')
                {
                    swal("The team does not exist in Onfleet!", {

                        icon: "warning",
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

                setDisabledButton(false);
            });
        }
    }

    const getTeam = (id) => {

        LoadingShow();

        listAllRole();
        listAllRoute();

        fetch(url_general +'team/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let team       = response.team;
            let listPrices = response.listPrices;

            setId(team.id);
            setIdRole(team.idRole);
            setName(team.name);
            setNameOfOwner(team.nameOfOwner);
            setAddress(team.address);
            setPhone(team.phone);
            setEmail(team.email);
            setPermissionDispatch(team.permissionDispatch);
            setStatus(team.status);
            setIdOnfleet(team.idOnfleet);

            setTimeout( () => {

                listPrices.map( data => {

                    console.log(data);
                    document.getElementById('minWeight'+ 1 + data.idCompany).value   = data.minWeight;
                    document.getElementById('maxWeight'+ 1 + data.idCompany).value   = data.maxWeight;         
                    document.getElementById('priceWeight'+ 1 + data.idCompany).value = data.price;

                    document.getElementById('minWeight'+ 2 + data.idCompany).value   = data.minWeight;
                    document.getElementById('maxWeight'+ 2 + data.idCompany).value   = data.maxWeight;
                    document.getElementById('priceWeight'+ 2 + data.idCompany).value = data.price;

                    document.getElementById('minWeight'+ 3 + data.idCompany).value   = data.minWeight;
                    document.getElementById('maxWeight'+ 3 + data.idCompany).value   = data.maxWeight;
                    document.getElementById('priceWeight'+ 3 + data.idCompany).value = data.price;
                });

            }, 100);
            
            /*setTimeout( () => {

                team.routes_team.forEach( teamRoute => {

                    document.getElementById('idCheck'+ teamRoute.route.name).checked = true;
                });

                handleChange();

            }, 100);*/

            handlerOpenModal(team.id);

            LoadingHide();
        });
    }

    const changeStatus = (id) => {

        swal({
            title: "You want to change the status of the Team?",
            text: "Change state!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'team/changeStatus/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Team status changed!", {

                            icon: "success",
                        });

                        listAllTeam(page);
                    }

                    LoadingHide();
                });
            }
        });
    }

    const deleteTeam = (id) => {

        swal({
            title: "You want to delete?",
            text: "Team will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'team/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Team was successfully eliminated!", {

                            icon: "success",
                        });

                        listAllTeam(page);
                    }

                    LoadingHide();
                });
            }
        });
    }

    ///////////////////////////////////////////////////////////////////////////////
//////////          MAINTENANCE RANGES        //////////////////////
    const [listCompany , setListCompany]                = useState([]);
    const [listRange, setListRange]                     = useState([]);
    const [idTeam, setIdTeam]                           = useState(0);
    const [idCompany, setIdCompany]                     = useState(0);
    const [Route, setRoute]                             = useState('');
    const [viewAddRange, setViewAddRange]               = useState('none');
    const [titleModalRange, setTitleModalRange]         = useState('');
    const [textButtonSaveRange, setTextButtonSaveRange] = useState('')
    const [idRange, setIdRange]                         = useState(0);
    const [minWeightRange, setMinWeightRange]           = useState('');
    const [maxWeightRange, setMaxWeightRange]           = useState('');
    const [priceWeightRange, setPriceWeightRange]       = useState('');
    const [fuelPercentageRange, setfuelPercentageRange] = useState('');

    const handlerAddRange = () => {

        clearFormRange();
        clearValidationRange();
        setViewAddRange('block');
        setTextButtonSaveRange('Save');

        if(idTeam != 0 && idCompany != 0 && Route != '')
        {
            listAllRange(idTeam, idCompany, Route);
        }
    }

    const handlerOpenModalRange = (idTeam, team) => {
 
        //listAllRange(idCompany);
        setListRange([]);
        setIdTeam(idTeam);
        setViewAddRange('none');
        setTitleModalRange('Team Prices Ranges: '+ team);

        clearValidationRange();

        let myModal = new bootstrap.Modal(document.getElementById('modalRangeInsert'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();
    }

    const listAllRange = (idTeam, idCompany, route) => {

        fetch(url_general +'range-price-team-route-company/list/'+ idTeam +'/'+ idCompany +'/'+ route)
        .then(res => res.json())
        .then((response) => {

            setListRange(response.rangeList);
        });
    }

    const handlerSaveRange = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idTeam', idTeam);
        formData.append('idCompany', idCompany);
        formData.append('route', Route);
        formData.append('minWeight', minWeightRange);
        formData.append('maxWeight', maxWeightRange);
        formData.append('price', priceWeightRange);
        formData.append('fuelPercentage', fuelPercentageRange);

        clearValidationRange();

        if(idRange == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'range-price-team-route-company/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Range was save!", {

                            icon: "success",
                        });

                        clearFormRange();
                        listAllRange(idTeam, idCompany, Route);
                    }
                    else if(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index +'Range').style.display = 'block';
                            document.getElementById(index +'Range').innerHTML     = response.errors[index][0];
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

            fetch(url_general +'range-price-team-route-company/update/'+ idRange, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction)
                {
                    listAllRange(idTeam, idCompany, Route);

                    swal("Store updated!", {

                        icon: "success",
                    });
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index +'Store').style.display = 'block';
                        document.getElementById(index +'Store').innerHTML     = response.errors[index][0];
                    }
                }

                LoadingHide();
            });
        }
    }

    const getRange = (id) => {

        clearValidationRange();

        fetch(url_general +'range-price-team-route-company/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let range = response.range;

            console.log(range);

            setIdRange(range.id);
            setMinWeightRange(range.minWeight);
            setMaxWeightRange(range.maxWeight);
            setPriceWeightRange(range.price);
            setfuelPercentageRange(range.fuelPercentage);
            setViewAddRange('block');
            setTextButtonSaveRange('Updated');
        });
    }

    const deleteRange = (id) => {

        swal({
            title: "You want to delete?",
            text: "Range will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'range-price-team-route-company/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Range deleted successfully!", {

                            icon: "success",
                        }); 

                        listAllRange(idTeam, idCompany, Route);
                    }
                });
            } 
        });
    }

    const optionsRoute = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const listInputPricesTeams = listCompany.map( (company, i) => {

        return (

            <tr>
                <td><b>{ company.name }</b></td>
                <td>
                    <input type="text" id={ 'minWeight'+ 1 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'minWeight'+ 2 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'minWeight'+ 3 + company.id } className="form-control form-group"/>
                </td>
                <td>
                    <input type="text" id={ 'maxWeight'+ 1 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'maxWeight'+ 2 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'maxWeight'+ 3 + company.id } className="form-control form-group"/>
                </td>
                <td>
                    <input type="text" id={ 'priceWeight'+ 1 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'priceWeight'+ 2 + company.id } className="form-control form-group"/>
                    <input type="text" id={ 'priceWeight'+ 3 + company.id } className="form-control form-group"/>
                </td>
            </tr>
        );
    })

    const listRangeTable = listRange.map( (range, i) => {

        return (

            <tr key={i}>
                <td><b>{ range.minWeight }</b></td>
                <td><b>{ range.maxWeight }</b></td>
                <td><b>{ range.price +' $' }</b></td>
                <td className="text-center">
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getRange(range.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>&nbsp;
                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteRange(range.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const clearForm = () => {

        setId(0);
        setIdRole(0);
        setName('');
        setNameOfOwner('');
        setAddress('');
        setPhone('');
        setEmail('');
        setStatus('Active');

        listCompany.map( company => {

            document.getElementById('minWeight'+ 1 + company.id).value = '';
            document.getElementById('maxWeight'+ 1 + company.id).value = '';
            document.getElementById('priceWeight'+ 1 + company.id).value = '';

            document.getElementById('minWeight'+ 2 + company.id).value = '';
            document.getElementById('maxWeight'+ 2 + company.id).value = '';
            document.getElementById('priceWeight'+ 2 + company.id).value = '';

            document.getElementById('minWeight'+ 3 + company.id).value = '';
            document.getElementById('maxWeight'+ 3 + company.id).value = '';
            document.getElementById('priceWeight'+ 3 + company.id).value = '';
        });
    }

    const clearFormRange = () => {

        setIdRange(0);
        setMinWeightRange('');
        setMaxWeightRange('');
        setPriceWeightRange('');
        setfuelPercentageRange('');
    }

    const clearValidation = () => {

        document.getElementById('idRole').style.display = 'none';
        document.getElementById('idRole').innerHTML     = '';

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';

        document.getElementById('nameOfOwner').style.display = 'none';
        document.getElementById('nameOfOwner').innerHTML     = '';

        document.getElementById('phone').style.display = 'none';
        document.getElementById('phone').innerHTML     = '';

        document.getElementById('email').style.display = 'none';
        document.getElementById('email').innerHTML     = '';

        document.getElementById('status').style.display = 'none';
        document.getElementById('status').innerHTML     = '';
    }

    const clearValidationRange = () => {

        document.getElementById('idCompanyRange').style.display = 'none';
        document.getElementById('idCompanyRange').innerHTML     = '';

        document.getElementById('routeRange').style.display = 'none';
        document.getElementById('routeRange').innerHTML     = '';

        document.getElementById('minWeightRange').style.display = 'none';
        document.getElementById('minWeightRange').innerHTML     = '';

        document.getElementById('maxWeightRange').style.display = 'none';
        document.getElementById('maxWeightRange').innerHTML     = '';

        document.getElementById('priceRange').style.display = 'none';
        document.getElementById('priceRange').innerHTML     = '';
    }

    const listUserTable = listUser.map( (user, i) => {

        return (

            <tr key={i}>
                <td>
                    <b>{ user.name }</b><br/>
                    { user.nameOfOwner }
                </td>
                <td>{ user.phone }</td>
                <td>{ user.email }</td>
                <td>{ user.idOnfleet }</td> 
                <td>
                    {
                        (
                            user.status == 'Active'
                            ?
                                <button className="alert alert-success font-weight-bold" onClick={ () => changeStatus(user.id) }>{ user.status }</button>
                            :
                                <button className="alert alert-danger font-weight-bold" onClick={ () => changeStatus(user.id) }>{ user.status }</button>
                        )
                    }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm mb-2" title="Editar" onClick={ () => getTeam(user.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>&nbsp;
                    {
                        (
                            user.deleteUser == 0
                            ?
                                <button className="btn btn-danger btn-sm mb-2" title="Delete" onClick={ () => deleteTeam(user.id) }>
                                    <i className="bx bxs-trash-alt"></i>
                                </button>
                            :
                                ''
                        )
                    }
                    &nbsp;
                    <button className="btn btn-success btn-sm mb-2" title="List Ranges Prices" onClick={ () => handlerOpenModalRange(user.id, user.name) }>
                        <i className="bx bxs-badge-dollar"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {
        console.log('roleee: ',role);
        return (

            (
                role.name == 'Team'
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                    ''
            )
        );
    });

    const handleChange = () => {

        let routesIds = '';

        listRoute.forEach( route => {

            if(document.getElementById('idCheck'+ route.name).checked)
            {
                routesIds = (routesIds == '' ? route.name : route.name +','+ routesIds);
            }
        });

        setIdsRoutes(routesIds);
    };

    const listAllRangeAux = (idCompany, route) => {

        clearFormRange();
        setTextButtonSaveRange('Save');

        if(idTeam != 0 && idCompany != 0 && route != '')
        {
            listAllRange(idTeam, idCompany, route);
        }
    }
    const changeCompany = (idCompany) => {

        setIdCompany(idCompany);

        listAllRangeAux(idCompany, Route);
    }

    const changeRoute = (route) => {

        setRoute(route);

        listAllRangeAux(idCompany, route);
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'range-price-team-route-company/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == true)
                {
                    swal("The file was imported successfully!", {

                        icon: "success",
                    });
                }
                else
                {
                    swal("Error importing file!", {

                        icon: "danger",
                    });
                }

                document.getElementById('fileImport').value = '';
                setViewButtonSave('none');

                LoadingHide();
            },
        );
    }

    const [optionsRouteSearch, setOptionsRouteSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRouteSearch([]);

        console.log(listRoutes);
        listRoutes.map( (route, i) => {

            optionsRouteSearch.push({ value: route.name, label: route.name });

            setOptionsRouteSearch(optionsRouteSearch);
        });
    }

    const [RouteSearch, setRouteSearch] = useState('');

    const handlerChangeRoute = (routes) => {

        setRouteSearch(routes.value);
    };

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-md">
                                            <form onSubmit={ handlerSaveTeam }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Role</label>
                                                                    <div id="idRole" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ idRole } className="form-control" onChange={ (e) => setIdRole(e.target.value) } required>
                                                                        { listRoleSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Team Name</label>
                                                                    <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ name } className="form-control" onChange={ (e) => setName(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Name of owner</label>
                                                                    <div id="nameOfOwner" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ nameOfOwner } className="form-control" onChange={ (e) => setNameOfOwner(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Phone</label>
                                                                    <div id="phone" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ phone } className="form-control" onChange={ (e) => setPhone(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Email</label>
                                                                    <div id="email" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ email } className="form-control" onChange={ (e) => setEmail(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Id Onfleet</label>
                                                                    <input type="text" value={ idOnfleet } className="form-control" readOnly/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row" >
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Status</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ status } className="form-control" onChange={ (e) => setStatus(e.target.value) } required>
                                                                        <option value="Active" >Active</option>
                                                                        <option value="Inactive" >Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label htmlFor="" className="form">Route :</label>
                                                                    <Select onChange={ (e) => handlerChangeRoute(e) } options={ optionsRouteSearch } />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <table className="table table-hover table-condensed">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>COMPANY</th>
                                                                                <th>MIN WEIGHT</th>
                                                                                <th>MAX WEIGHT</th>
                                                                                <th>PRICE</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            { listInputPricesTeams }
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>{ textButtonSave }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const modalRangeInsert = <React.Fragment>
                                    <div className="modal fade" id="modalRangeInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-lg">
                                            <div className="modal-content">
                                                <form onSubmit={ handlerSaveRange }>
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModalRange }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body" style={ {display: viewAddRange } }>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label className="form">COMPANY</label>
                                                                <div id="idCompanyRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                <select className="form-control" onChange={ (e) => changeCompany(e.target.value) }>
                                                                    <option value="">Select...</option>
                                                                </select>
                                                            </div> 
                                                            <div className="col-lg-6 form-group">
                                                                <label className="form">ROUTE</label>
                                                                <div id="routeRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                <select className="form-control" onChange={ (e) => changeRoute(e.target.value) } required>
                                                                    <option value="" style={ {display: 'none'} }>Seleccione una ruta</option>
                                                                    { optionsRoute }
                                                                </select>
                                                            </div>
                                                        </div> 
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label className="form">MIN. WEIGHT</label>
                                                                <div id="minWeightRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="number" className="form-control" value={ minWeightRange } min="1" max="999" onChange={ (e) => setMinWeightRange(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label className="form">MAX WEIGHT</label>
                                                                <div id="maxWeightRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="number" className="form-control" value={ maxWeightRange } min="1" max="999" onChange={ (e) => setMaxWeightRange(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label className="form">Price $</label>
                                                                <div id="priceRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="number" className="form-control" value={ priceWeightRange } min="1" max="999" step="0.0001" maxLength="100" onChange={ (e) => setPriceWeightRange(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                            <button className="btn btn-primary form-control">{ textButtonSaveRange }</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <div className="modal-footer">
                                                    <div className="row">
                                                        <div className="col-lg-12 form-group pull-right">
                                                            <button type="button" className="btn btn-success btn-sm" onClick={ () => handlerAddRange() }>
                                                                <i className="bx bxs-plus-square"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <table className="table table-condensed table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>MIN. WEIGHT</th>
                                                                <th>MAX. WEIGHT</th>
                                                                <th>BASE PRICE</th>
                                                                <th>ACTIONS</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            { listRangeTable }
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalCategoryInsert }
            { modalRangeInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2">
                                        <button className="btn btn-success form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                    <div className="col-lg-3 mb-3">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <button type="button" className="btn btn-primary form-control" onClick={ () => onBtnClickFile() }>
                                                    <i className="bx bxs-file"></i> Import prices ranges
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImport() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>NAME</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
                                                <th>ID ONFLEET</th>
                                                <th>STATUS</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listUserTable }
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
                        totalItemsCount={totalTeam}
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

export default Team;

// DOM element
if (document.getElementById('team')) {
    ReactDOM.render(<Team />, document.getElementById('team'));
}
