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
    const [emailCC, setEmailCC]                       = useState('');//cambio
    const [surcharge, setSurcharge]                   = useState(1);
    const [roundWeight, setRoundWeight]               = useState(1);
    const [twoAttempts, setTwoAttempts]               = useState(1);
    const [status, setStatus]                         = useState('');
    const [idsRoutes, setIdsRoutes]                   = useState('');
    const [permissionDispatch, setPermissionDispatch] = useState(0);
    const [idOnfleet, setIdOnfleet]                   = useState('');

    const [disabledButton, setDisabledButton] = useState(false);

    const [listUser, setListUser]                             = useState([]);
    const [listRole, setListRole]                             = useState([]);
    const [listRoute, setListRoute]                           = useState([]);
    const [listConfigurationPrice, setListConfigurationPrice] = useState([]);
    const [listTeamRoute, setListTeamRoute]                   = useState([]);

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

    const [defaultOptionConfiguration, setDefaultOptionConfiguration] = useState(null);
    const [valueOptionConfiguration, setValueOptionConfiguration]     = useState('');

    const handlerOpenModal = (id) => {

        setRouteSearch(null);
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

            handlerChangeRoute([]);
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveTeam = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('emailCC', emailCC);
        formData.append('status', status);
        formData.append('surcharge', surcharge);
        formData.append('roundWeight', roundWeight);
        formData.append('twoAttempts', twoAttempts);

        clearValidation();

        if(idTeam == 0)
        {
            LoadingShowMap();

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

                        handlerChangeRoute([]);
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

                    LoadingHideMap();

                    setDisabledButton(false);
                },
            );
        }
        else
        {
            setDisabledButton(true);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'team/update/'+ idTeam, {
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
                else if(response.stateAction == 'routesExists')
                {
                    swal("the following routes ["+ response.routesExists +"] are already in another configuration!", {

                        icon: "warning",
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

        setRouteSearch(null);
        LoadingShow();
        clearForm();
        listAllRole();
        listAllRoute();
        handlerChangeRoute([]);

        fetch(url_general +'team/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let team       = response.team;
            let listPrices = response.listPrices;

            setIdTeam(team.id);
            setIdRole(team.idRole);
            setName(team.name);
            setNameOfOwner(team.nameOfOwner);
            setAddress(team.address);
            setPhone(team.phone);
            setEmail(team.email);
            setEmailCC(team.emailCC);
            setPermissionDispatch(team.permissionDispatch);
            setStatus(team.status);
            setIdOnfleet(team.idOnfleet);
            setSurcharge(team.surcharge);
            setRoundWeight(team.roundWeight);
            setTwoAttempts(team.twoAttempts)
            /*setTimeout( () => {

                console.log(listPrices);

                for(var i = 0; i < listPrices.length; i++)
                {
                    setRouteSearch(listPrices[i].route);

                    if((i % 3) == 0)
                    {
                        document.getElementById('minWeight'+ 1 + listPrices[i].idCompany).value   = listPrices[i].minWeight;
                        document.getElementById('maxWeight'+ 1 + listPrices[i].idCompany).value   = listPrices[i].maxWeight;         
                        document.getElementById('priceWeight'+ 1 + listPrices[i].idCompany).value = listPrices[i].price;

                        document.getElementById('minWeight'+ 2 + listPrices[i].idCompany).value   = listPrices[i + 1].minWeight;
                        document.getElementById('maxWeight'+ 2 + listPrices[i].idCompany).value   = listPrices[i + 1].maxWeight;
                        document.getElementById('priceWeight'+ 2 + listPrices[i].idCompany).value = listPrices[i + 1].price;

                        document.getElementById('minWeight'+ 3 + listPrices[i].idCompany).value   = listPrices[i + 2].minWeight;
                        document.getElementById('maxWeight'+ 3 + listPrices[i].idCompany).value   = listPrices[i + 2].maxWeight;
                        document.getElementById('priceWeight'+ 3 + listPrices[i].idCompany).value = listPrices[i + 2].price;
                    }
                }

            }, 100);
            
            setTimeout( () => {

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
    const [buttonAddPriceCompany, setButtonAddPriceCompany] = useState('block');

    const [listCompany , setListCompany]                = useState([]);
    const [listRange, setListRange]                     = useState([]);
    const [listPriceByRoute, setListPriceByRoute]       = useState([]);
    const [listPriceByCompany, setListPriceByCompany]   = useState([]);
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
    const [routePrice, setRoutePrice]                   = useState('');
    const [priceByRoute, setPriceByRoute]               = useState('');
    const [priceByCompany, setPriceByCompany]           = useState('');
    const [routeByCompany, setRouteByCompany]           = useState('');
    const [companyPrice, setCompanyPrice]               = useState(0);
    const [baseRateRange, setBaseRateRange]             = useState(0);
    const [fuelPercentageRange, setfuelPercentageRange] = useState('');

    const handlerAddRange = () => {

        clearFormRange();
        clearValidationRange();
        setViewAddRange('block');
        setTextButtonSaveRange('Save');

        if(idTeam != 0 && idCompany != 0 && Route != '')
        {
            listAllRangePriceBaseTeam(idTeam, idCompany, Route);
        }
    }

    const handlerOpenModalRange = (idTeam, team) => {
        
        setListRange([]);
        setIdTeam(idTeam);
        setViewAddRange('none');
        setTitleModalRange('Team Prices Ranges: '+ team);

        fetch(url_general +'range-price-base-team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListRange(response.rangeList);

            let myModal = new bootstrap.Modal(document.getElementById('modalRangePriceBaseTeam'), {

                keyboard: false,
                backdrop: 'static',
            });
     
            myModal.show();
        });
    }

    const handlerOpenModalRangeByRoute = (idTeam, team) => {
        
        LoadingShowMap();

        setListPriceByRoute([]);
        setIdTeam(idTeam);
        setViewAddRange('none');
        setTitleModalRange('Team Prices By Route: '+ team);

        fetch(url_general +'range-price-team-by-route/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            LoadingHideMap();

            setListPriceByRoute(response.rangeList);

            let myModal = new bootstrap.Modal(document.getElementById('modalRangePriceByRouteTeam'), {

                keyboard: false,
                backdrop: 'static',
            });
     
            myModal.show();
        });
    }

    const handlerOpenModalRangeByCompany = (idTeam, team) => {
        
        LoadingShowMap();

        clearValidationPriceByCompany();
        clearFormPriceByCompany();

        setListPriceByCompany([]);
        setIdTeam(idTeam);
        setViewAddRange('none');
        setTitleModalRange('Team - Prices - By Company - Route: '+ team);

        fetch(url_general +'range-price-team-by-company/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            LoadingHideMap();
            
            setListPriceByCompany(response.rangeList);

            let myModal = new bootstrap.Modal(document.getElementById('modalRangePriceByCompanyTeam'), {

                keyboard: false,
                backdrop: 'static',
            });
     
            myModal.show();
        });

        listAllRangePriceBaseTeam(idTeam)
    }

    const listAllRange = (idTeam, idCompany, route) => {

        fetch(url_general +'range-price-team-route-company/list/'+ idTeam +'/'+ idCompany +'/'+ route)
        .then(res => res.json())
        .then((response) => {

            setListRange(response.rangeList);
        });
    }

    const listAllRangePriceBaseTeam = (idTeam) => {

        fetch(url_general +'range-price-base-team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListRange(response.rangeList);
        });
    }

    const listAllPriceByRoute = (idTeam) => {

        fetch(url_general +'range-price-team-by-route/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListPriceByRoute(response.rangeList);
        });
    }

    const listAllPriceByCompany = (idTeam) => {

        fetch(url_general +'range-price-team-by-company/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListPriceByCompany(response.rangeList);
        });
    }

    const handlerSaveRange = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idTeam', idTeam);
        formData.append('minWeight', minWeightRange);
        formData.append('maxWeight', maxWeightRange);
        formData.append('price', priceWeightRange);

        clearValidationRange();

        if(idRange == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'range-price-base-team/insert', {
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
                        listAllRangePriceBaseTeam(idTeam);
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

            fetch(url_general +'range-price-base-team/update/'+ idRange, {
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
                    listAllRangePriceBaseTeam(idTeam);

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

        fetch(url_general +'range-price-base-team/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let range = response.range;

            console.log(range);

            setIdRange(range.id);
            setMinWeightRange(range.minWeight);
            setMaxWeightRange(range.maxWeight);
            setPriceWeightRange(range.price);
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
                fetch(url_general +'range-price-base-team/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Range deleted successfully!", {

                            icon: "success",
                        }); 

                        listAllRangePriceBaseTeam(idTeam);
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

    const handlerSavePriceByRoute = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idTeam', idTeam);
        formData.append('route', routePrice);
        formData.append('price', priceByRoute);

        clearValidationRange();

        if(idRange == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'range-price-team-by-route/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Price was save!", {

                            icon: "success",
                        });

                        clearFormPriceByRoute();
                        listAllPriceByRoute(idTeam);
                    }
                    else if(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index +'ByRoute').style.display = 'block';
                            document.getElementById(index +'ByRoute').innerHTML     = response.errors[index][0];
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

            fetch(url_general +'range-price-team-by-route/update/'+ idRange, {
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
                    listAllPriceByRoute(idTeam);

                    swal("Price updated!", {

                        icon: "success",
                    });
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index +'ByRoute').style.display = 'block';
                        document.getElementById(index +'ByRoute').innerHTML     = response.errors[index][0];
                    }
                }

                LoadingHide();
            });
        }
    }

    const getPriceByRoute = (id) => {

        clearValidationRange(); 

        fetch(url_general +'range-price-team-by-route/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let range = response.range;

            console.log(range);

            setIdRange(range.id);
            setRoutePrice(range.route);
            setPriceByRoute(range.price);
            setViewAddRange('block');
            setTextButtonSaveRange('Updated');
        });
    }
    
    const deletePriceByRoute = (id) => {

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
                fetch(url_general +'range-price-team-by-route/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Range deleted successfully!", {

                            icon: "success",
                        }); 

                        listAllPriceByRoute(idTeam);
                    }
                });
            } 
        });
    }

    const handlerSavePriceByCompany = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idTeam', idTeam);
        formData.append('idCompany', companyPrice);
        formData.append('routeByCompany', routeByCompany);
        formData.append('price', priceByCompany);
        formData.append('idRangeRate', baseRateRange)

        clearValidationPriceByCompany();

        if(idRange == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShowMap();

            fetch(url_general +'range-price-team-by-company/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Price was save!", {

                            icon: "success",
                        });

                        clearFormPriceByCompany();

                        setButtonAddPriceCompany('block');
                        setViewAddRange('none');

                        let select = document.getElementById("selectIdCompany");
                        select.value = 0;

                        listAllPriceByCompany(idTeam);
                    }
                    else if(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index +'ByCompany').style.display = 'block';
                            document.getElementById(index +'ByCompany').innerHTML     = response.errors[index][0];
                        }
                    }

                    LoadingHideMap();
                },
            );
        }
        else
        {
            LoadingShowMap();

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'range-price-team-by-company/update/'+ idRange, {
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
                    clearFormPriceByCompany();

                    setButtonAddPriceCompany('block');
                    setViewAddRange('none');

                    let select = document.getElementById("selectIdCompany");
                    select.value = 0;

                    listAllPriceByCompany(idTeam);

                    swal("Price updated!", {

                        icon: "success",
                    });
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index +'ByCompany').style.display = 'block';
                        document.getElementById(index +'ByCompany').innerHTML     = response.errors[index][0];
                    }
                }

                LoadingHideMap();
            });
        }
    }

    const getPriceByCompany = (id) => {

        clearValidationPriceByCompany(); 

        LoadingShowMap();

        fetch(url_general +'range-price-team-by-company/get/'+ id)
        .then(response => response.json())
        .then(response => {

            LoadingHideMap();

            setButtonAddPriceCompany('none');

            let range = response.range;

            let select = document.getElementById("selectIdCompany");
            select.value = range.idCompany;

            let selectRangeRateTeam = document.getElementById("selectIdRangeRate");
            selectRangeRateTeam.value = range.idRangeRate;

            setIdRange(range.id);
            setCompanyPrice(range.idCompany);
            setBaseRateRange(range.idRangeRate)
            setRouteByCompany(range.route);
            setPriceByCompany(range.price);
            setViewAddRange('block');
            setTextButtonSaveRange('Updated');
        });
    }
    
    const deletePriceByCompany = (id) => {

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
                LoadingShowMap();

                fetch(url_general +'range-price-team-by-company/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    LoadingHideMap();

                    if(response.stateAction)
                    {
                        swal("Range deleted successfully!", {

                            icon: "success",
                        }); 

                        listAllPriceByCompany(idTeam);
                    }
                });
            } 
        });
    }

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

    const listPriceByRouteTable = listPriceByRoute.map( (range, i) => {

        return (

            <tr key={i}>
                <td><b>{ range.route }</b></td>
                <td><b>{ range.price +' $' }</b></td>
                <td className="text-center">
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getPriceByRoute(range.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>&nbsp;
                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deletePriceByRoute(range.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listPriceByCompanyTable = listPriceByCompany.map( (range, i) => {

        return (

            <tr key={i}>
                <td><b>{ range.company }</b></td>
                <td>
                    {
                        range.range_rate_team
                        ?
                            <b>{ range.range_rate_team.minWeight +' - '+ range.range_rate_team.maxWeight }</b>
                        :
                            ''
                    }
                </td>
                <td><b>{ range.route }</b></td>
                <td><b>{ range.price +' $' }</b></td>
                <td className="text-center">
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getPriceByCompany(range.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>&nbsp;
                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deletePriceByCompany(range.id) }>
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
        setEmailCC('');
        setStatus('Active');
    }

    const clearFormRange = () => {

        setIdRange(0);
        setMinWeightRange('');
        setMaxWeightRange('');
        setPriceWeightRange('');
        setfuelPercentageRange('');
    }

    const clearFormPriceByRoute = () => {

        setIdRange(0);
        setRoutePrice('')
        setPriceByRoute('');
    }

    const clearFormPriceByCompany = () => {

        let select = document.getElementById("selectIdCompany");
        select.value = '';

        setIdRange(0);
        setRouteByCompany('');
        setBaseRateRange('')
        setCompanyPrice('');
        setPriceByCompany('');
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

        document.getElementById('minWeightRange').style.display = 'none';
        document.getElementById('minWeightRange').innerHTML     = '';

        document.getElementById('maxWeightRange').style.display = 'none';
        document.getElementById('maxWeightRange').innerHTML     = '';

        document.getElementById('priceRange').style.display = 'none';
        document.getElementById('priceRange').innerHTML     = '';
    }

    const clearValidationPriceByCompany = () => {

        idRangeRate
        document.getElementById('idCompanyByCompany').style.display = 'none';
        document.getElementById('idCompanyByCompany').innerHTML     = '';

        document.getElementById('idRangeRate').style.display = 'none';
        document.getElementById('idRangeRate').innerHTML     = '';

        document.getElementById('routeByCompanyByCompany').style.display = 'none';
        document.getElementById('routeByCompanyByCompany').innerHTML     = '';

        document.getElementById('priceByCompany').style.display = 'none';
        document.getElementById('priceByCompany').innerHTML     = '';
    }

    const listUserTable = listUser.map( (user, i) => {

        return (

            <tr key={i}>
                <td>
                    <b className="text-primary">{ user.id }</b><br/>
                    <b>{ user.name }</b><br/>
                    { user.nameOfOwner }
                </td>
                <td>{ user.phone }</td>
                <td>{ user.email }</td>
                <td>
                    {
                        (
                            user.roundWeight
                            ?
                                <div className="alert alert-success font-weight-bold">YES</div>
                            :
                                <div className="alert alert-danger font-weight-bold">NO</div>
                        )
                    }
                </td>
                <td>
                    {
                        (
                            user.surcharge
                            ?
                                <div className="alert alert-success font-weight-bold">YES</div>
                            :
                                <div className="alert alert-danger font-weight-bold">NO</div>
                        )
                    }
                </td>
                <td>
                    {
                        (
                            user.twoAttempts
                            ?
                                <div className="alert alert-success font-weight-bold">YES</div>
                            :
                                <div className="alert alert-danger font-weight-bold">NO</div>
                        )
                    }
                </td>
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
                    &nbsp;
                    <button className="btn btn-warning btn-sm mb-2" title="List Ranges By Routes" onClick={ () => handlerOpenModalRangeByRoute(user.id, user.name) } style={ {display: 'none'}}>
                        <i className="bx bxs-badge-dollar"></i>
                    </button>
                    <button className="btn btn-danger btn-sm mb-2" title="List Ranges By Routes" onClick={ () => handlerOpenModalRangeByCompany(user.id, user.name) }>
                        <i className="bx bxs-badge-dollar"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {
        //console.log('roleee: ',role);
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
            listAllRangePriceBaseTeam(idTeam, idCompany, route);
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

    const [RouteSearch, setRouteSearch] = useState(null);
    const [RouteOld, setRouteOld]       = useState(null);

    const [RoutesSelect, setRoutesSelect] = useState([]);

    const handlerChangeRoute = (routes) => {

        console.log(routes);

        let routesList   = [];
        let routesSearch = '';

        if(routes.length != 0)
        {
            routes.map( (route) => {

                routesList.push({label: route.value, value: route.value});
                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });
            //listAllPackageDispatch(1, StateSearch, routesSearch);
        }

        setRoutesSelect(routesList);
        setRouteSearch(routesSearch);
    };

    const listPricesTeams = (routePrice) => {

        let routePricesTeams = [];

        if(routePrice != null)
        {
            let auxlistPricesTeams = routePrice.split(',');

            auxlistPricesTeams.forEach( route => {

                routePricesTeams.push({label: route, value: route});
            });
        }
        
        setRoutesSelect(routePricesTeams);
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const listConfigurationOption = listConfigurationPrice.map( (configuration, i) => {

        return (

            <option value={ configuration.route }>{ configuration.route }</option>
        );
    });

    const handlerResetPassword = (emailUser) => {

        swal({
            title: "You want to reset password?",
            text: "Password will be reset!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'user/resetPassword/'+ emailUser)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction == true)
                    {
                        swal("Password reset successfully!", {

                            icon: "success",
                        });
                    }
                    else if(response.stateAction == false)
                    {
                        swal(response.message, {

                            icon: "warning",
                        });
                    }
                });
            }
        });
    }

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={ company.id }>{ company.name }</option>
    });

    const optionBaseRateRange = listRange.map( (rate, i) => {

        return <option value={ rate.id }>{ rate.minWeight +' - '+ rate.maxWeight }</option>
    });

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
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <a href="#" className="text-danger" onClick={ () => handlerResetPassword(email)  }><b><u>Reset Password</u></b></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Role</label>
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
                                                                    <label className="form">Team Name</label>
                                                                    <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ name } className="form-control" onChange={ (e) => setName(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Name of owner</label>
                                                                    <div id="nameOfOwner" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ nameOfOwner } className="form-control" onChange={ (e) => setNameOfOwner(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Phone</label>
                                                                    <div id="phone" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ phone } className="form-control" onChange={ (e) => setPhone(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Email</label>
                                                                    <div id="email" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ email } className="form-control" onChange={ (e) => setEmail(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Id Onfleet</label>
                                                                    <input type="text" value={ idOnfleet } className="form-control" readOnly/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Surcharge</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ surcharge } className="form-control" onChange={ (e) => setSurcharge(e.target.value) } required>
                                                                        <option value="1" >YES</option>
                                                                        <option value="0" >NO</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Round Weight</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ roundWeight } className="form-control" onChange={ (e) => setRoundWeight(e.target.value) } required>
                                                                        <option value="1" >YES</option>
                                                                        <option value="0" >NO</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Two Attempts</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ twoAttempts } className="form-control" onChange={ (e) => setTwoAttempts(e.target.value) } required>
                                                                        <option value="1" >YES</option>
                                                                        <option value="0" >NO</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Status</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ status } className="form-control" onChange={ (e) => setStatus(e.target.value) } required>
                                                                        <option value="Active" >Active</option>
                                                                        <option value="Inactive" >Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Additional Emails (optional)</label>
                                                                    <div id="emailCC" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ emailCC } className="form-control" onChange={ (e) => setEmailCC(e.target.value) } placeholder="ejemplo@correo.com, otro@correo.com" />
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

    const modalRangePriceBaseTeam = <React.Fragment>
                                        <div className="modal fade" id="modalRangePriceBaseTeam" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div className="modal-dialog modal-lg">
                                                <div className="modal-content">
                                                    <form onSubmit={ handlerSaveRange }>
                                                        <div className="modal-header">
                                                            <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModalRange }</h5>
                                                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div className="modal-body" style={ {display: viewAddRange } }>
                                                            <div className="row">
                                                                <div className="col-lg-12 form-group">
                                                                    <h4 className="text-primary">Price Range Data</h4>
                                                                </div>
                                                            </div>
                                                            <div className="row">
                                                                <div className="col-lg-3 form-group">
                                                                    <label className="form">MIN. WEIGHT</label>
                                                                    <div id="minWeightRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" className="form-control" value={ minWeightRange } min="0" max="999" step="0.01" onChange={ (e) => setMinWeightRange(e.target.value) } required/>
                                                                </div>
                                                                <div className="col-lg-3 form-group">
                                                                    <label className="form">MAX WEIGHT</label>
                                                                    <div id="maxWeightRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" className="form-control" value={ maxWeightRange } min="0" max="999" step="0.01" onChange={ (e) => setMaxWeightRange(e.target.value) } required/>
                                                                </div>
                                                                <div className="col-lg-3 form-group">
                                                                    <label className="form">Price $</label>
                                                                    <div id="priceRange" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" className="form-control" value={ priceWeightRange } min="1" max="999" step="0.0001" onChange={ (e) => setPriceWeightRange(e.target.value) } required/>
                                                                </div>
                                                                <div className="col-lg-3 form-group">
                                                                    <label className="text-white">--</label>
                                                                    <button className="btn btn-primary form-control">{ textButtonSaveRange }</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <div className="modal-footer">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group pull-right" style={ {display: buttonAddPriceCompany } }>
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

    const modalRangePriceByRouteTeam =  <React.Fragment>
                                            <div className="modal fade" id="modalRangePriceByRouteTeam" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div className="modal-dialog modal-lg">
                                                    <div className="modal-content">
                                                        <form onSubmit={ handlerSavePriceByRoute }>
                                                            <div className="modal-header">
                                                                <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModalRange }</h5>
                                                                <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div className="modal-body" style={ {display: viewAddRange } }>
                                                                <div className="row">
                                                                    <div className="col-lg-12 form-group">
                                                                        <h4 className="text-primary">Price By Route</h4>
                                                                    </div>
                                                                </div>
                                                                <div className="row">
                                                                    <div className="col-lg-4 form-group">
                                                                        <label className="form">ROUTE</label>
                                                                        <div id="routeByRoute" className="text-danger" style={ {display: 'none'} }></div>
                                                                        <input type="text" className="form-control" value={ routePrice } onChange={ (e) => setRoutePrice(e.target.value) } required/>
                                                                    </div>
                                                                    <div className="col-lg-4 form-group">
                                                                        <label className="form">PRICE $</label>
                                                                        <div id="priceByRoute" className="text-danger" style={ {display: 'none'} }></div>
                                                                        <input type="number" className="form-control" value={ priceByRoute } min="-999" max="999" step="0.0001" onChange={ (e) => setPriceByRoute(e.target.value) } required/>
                                                                    </div>
                                                                    <div className="col-lg-4 form-group">
                                                                        <label className="text-white">--</label>
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
                                                                        <th>ROUTE</th>
                                                                        <th>PRICE</th>
                                                                        <th>ACTIONS</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    { listPriceByRouteTable }
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </React.Fragment>;

    const modalRangePriceByCompanyTeam =    <React.Fragment>
                                                <div className="modal fade" id="modalRangePriceByCompanyTeam" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div className="modal-dialog modal-lg">
                                                        <div className="modal-content">
                                                            <form onSubmit={ handlerSavePriceByCompany }>
                                                                <div className="modal-header">
                                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModalRange }</h5>
                                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div className="modal-body" style={ {display: viewAddRange } }>
                                                                    <div className="row">
                                                                        <div className="col-lg-12 form-group">
                                                                            <h4 className="text-primary">Price By Company</h4>
                                                                        </div>
                                                                    </div>
                                                                    <div className="row">
                                                                        <div className="col-lg-3 form-group">
                                                                            <label className="form">COMPANY</label>
                                                                            <div id="idCompanyByCompany" className="text-danger" style={ {display: 'none'} }></div>
                                                                            <select name="" id="selectIdCompany" className="form-control" onChange={ (e) => setCompanyPrice(e.target.value) }>
                                                                                <option value="0">Select...</option>
                                                                                { optionCompany }
                                                                            </select>
                                                                        </div>
                                                                        <div className="col-lg-3 form-group">
                                                                            <label className="form">BASE RATE RANGE</label>
                                                                            <div id="idRangeRate" className="text-danger" style={ {display: 'none'} }></div>
                                                                            <select id="selectIdRangeRate" className="form-control" onChange={ (e) => setBaseRateRange(e.target.value) }>
                                                                                <option value="0">Select...</option>
                                                                                { optionBaseRateRange }
                                                                            </select>
                                                                        </div>
                                                                        <div className="col-lg-3 form-group">
                                                                            <label className="form">ROUTE</label>
                                                                            <div id="routeByCompanyByCompany" className="text-danger" style={ {display: 'none'} }></div>
                                                                            <input type="text" className="form-control" value={ routeByCompany } maxLength="20" step="0.0001" onChange={ (e) => setRouteByCompany(e.target.value) }/>
                                                                        </div>
                                                                        <div className="col-lg-3 form-group">
                                                                            <label className="form">PRICE $</label>
                                                                            <div id="priceByCompany" className="text-danger" style={ {display: 'none'} }></div>
                                                                            <input type="number" className="form-control" value={ priceByCompany } min="-999" max="999" step="0.0001" onChange={ (e) => setPriceByCompany(e.target.value) } required/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="row">
                                                                        <div className="col-lg-3 form-group">
                                                                            <button className="btn btn-primary form-control">{ textButtonSaveRange }</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                            <div className="modal-footer">
                                                                <div className="row">
                                                                    <div className="col-lg-12 form-group pull-right" style={ {display: buttonAddPriceCompany } }>
                                                                        <button type="button" className="btn btn-success btn-sm" onClick={ () => handlerAddRange() }>
                                                                            <i className="bx bxs-plus-square"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <table className="table table-condensed table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>COMPANY</th>
                                                                            <th>BASE RATE RANGE</th>
                                                                            <th>ROUTE</th>
                                                                            <th>PRICE</th>
                                                                            <th>ACTIONS</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { listPriceByCompanyTable }
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
            { modalRangePriceBaseTeam }
            { modalRangePriceByRouteTeam }
            { modalRangePriceByCompanyTeam }
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
                                    <div className="col-lg-3 mb-3" style={ {display: 'none'} }>
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
                            <div className="row">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>NAME</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
                                                <th>ROUND WEIGHT</th>
                                                <th>SURCHARGE</th>
                                                <th>TWO ATTEMPTS</th>
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
