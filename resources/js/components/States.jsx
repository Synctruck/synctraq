import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function States() {

    const [id, setId]                  = useState(0);
    const [name, setName]              = useState('');
    const [idClient, setIdClient]      = useState(0);
    const [nameClient, setNameClient]  = useState('');

    const [listState, setListState]       = useState([]);
    const [listManifest, setListManifest] = useState([]);
    const [listClient, setListClient]     = useState([]);

    const [pageState, setPageState]           = useState(1);
    const [totalPageState, setTotalPageState] = useState(0);
    const [totalState, setTotalState]         = useState(0);

    const [pageManifest, setPageManifest]           = useState(1);
    const [totalPageManifest, setTotalPageManifest] = useState(0);
    const [totalManifest, setTotalManifest]         = useState(0);

    const [pageClient, setPageClient]           = useState(1);
    const [totalPageClient, setTotalPageClient] = useState(0);
    const [totalClient, setTotalClient]         = useState(0);

    const [titleModal, setTitleModal]             = useState('');
    const [titleModalClient, setTitleModalClient] = useState('');

    const [textSearchState, setSearchState]       = useState('');
    const [textSearchManifest, setSearchManifest] = useState('');
    const [textSearchClient, setSearchClient]     = useState('');

    const [textButtonSave, setTextButtonSave]             = useState('Guardar');
    const [textButtonSaveClient, setTextButtonSaveClient] = useState('Guardar');

    useEffect(() => {

        listAllState(pageState);

    }, [textSearchState])

    useEffect(() => {

        listAllManifest(pageManifest);

    }, [textSearchManifest])

    useEffect(() => {

        //listAllClient(pageClient);

    }, [textSearchClient])

    const handlerChangePageState = (pageNumber) => {

        listAllState(pageNumber);
    }

    const handlerChangePageManifest = (pageNumber) => {

        setPageManifest(pageNumber);
        listAllManifest(pageNumber);
    }

    const handlerChangePageClient = (pageNumber) => {

        listAllClient(pageNumber);
    }

    const listAllState = (pageNumber) => {

        fetch(url_general +'anti-scan/list?page='+ pageNumber +'&textSearch='+ textSearchState)
        .then(res => res.json())
        .then((response) => {

            setListState(response.stateList.data);
            setPageState(response.stateList.current_page);
            setTotalPageState(response.stateList.per_page);
            setTotalState(response.stateList.total);
        });
    }

    const listAllManifest = (pageNumber) => {

        fetch(url_general +'package-manifest/list/0/all/all?page='+ pageNumber +'&textSearch='+ textSearchManifest)
        .then(res => res.json())
        .then((response) => {

            setListManifest(response.packageList.data);
            setPageManifest(response.packageList.current_page);
            setTotalPageManifest(response.packageList.per_page);
            setTotalManifest(response.packageList.total);
        });
    }

    const listAllClient = (pageNumber) => {

        fetch(url_general +'client/list?page='+ pageNumber +'&textSearch='+ textSearchClient)
        .then(res => res.json())
        .then((response) => {

            setListClient(response.clientList.data);
            setPageClient(response.clientList.current_page);
            setTotalPageClient(response.clientList.per_page);
            setTotalClient(response.clientList.total);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update State')
            setTextButtonSave('Update');
        }
        else
        {
            clearForm();
            setTitleModal('Add State')
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalStateInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveState = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('name', name);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'anti-scan/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("State was registered!", {

                            icon: "success",
                        });

                        listAllState(1);
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

            fetch(url_general +'anti-scan/update/'+ id, {
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
                    listAllState(1);

                    swal("Status updated!", {

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

                LoadingHide();
            });
        }
    }

    const getState = (id) => {

        fetch(url_general +'anti-scan/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let state = response.state;

            setId(state.id);
            setName(state.name);

            handlerOpenModal(state.id);
        });
    }

    const deleteState = (id) => {

        swal({
            title: "Want to delete?",
            text: "Status will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'anti-scan/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Status removed successfully!", {

                            icon: "success",
                        });

                        listAllState(1);
                    }
                });
            } 
        });
    }

    const handlerUpdateState = () => {

        var checkboxes = document.getElementsByName('checkAntiScanState');

        let countCheck = 0;

        let valuesCheck = '';

        for(var i = 0; i < checkboxes.length ; i++)
        {
            if(checkboxes[i].checked)
            {
                valuesCheck = (valuesCheck == '' ? checkboxes[i].value : valuesCheck +','+ checkboxes[i].value);

                countCheck++;
            }
        }

        const formData = new FormData();

        formData.append('valuesCheck', valuesCheck);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'anti-scan/update/'+ 1, {
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
                listAllState(1);

                swal("Se actualizó los estados!", {

                    icon: "success",
                });
            }

            LoadingHide();
        });
    }

    const handlerUpdateManifest = () => {

        var checkboxes = document.getElementsByName('checkAntiScanManifest');

        let countCheck = 0;

        let valuesCheck = '';

        for(var i = 0; i < checkboxes.length ; i++)
        {
            if(checkboxes[i].checked)
            {
                valuesCheck = (valuesCheck == '' ? checkboxes[i].value : valuesCheck +','+ checkboxes[i].value);

                countCheck++;
            }
        }

        const formData = new FormData();

        formData.append('valuesCheck', valuesCheck);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'package-manifest/update/filter', {
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
                listAllManifest(pageManifest);

                swal("Se actualizó los Paquetes!", {

                    icon: "success",
                });
            }

            LoadingHide();
        });
    }

    const clearForm = () => {

        setId(0);
        setName('');
    }

    const clearValidation = () => {

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';
    }

    const listStateTable = listState.map( (state, i) => {

        return (

            <tr key={i}>
                <td>
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ state.id } name="checkAntiScanState" value={ state.id } defaultChecked={ state.filter ? true : false }/>
                </td>
                <td>{ state.name }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Edit" onClick={ () => getState(state.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    <button className="btn btn-danger btn-sm" title="Delete" onClick={ () => deleteState(state.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const handlerCheckbox = (Reference_Number_1, filter) => {

        if(filter == 1)
        {
            document.getElementById('idCheck'+ Reference_Number_1).checked = false;
        }
        else
        {
            document.getElementById('idCheck'+ Reference_Number_1).checked = true;
        }

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);

        fetch(url_general +'package-manifest/filter-check', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                LoadingHide();
                listAllManifest(pageManifest);
            },
        );
    }

    const listManifestTable = listManifest.map( (packageManifest, i) => {

        return (

            <tr key={i}>
                <td>
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ packageManifest.Reference_Number_1 } checked={ packageManifest.filter == 1 ? true : false } onChange={ () => handlerCheckbox(packageManifest.Reference_Number_1, packageManifest.filter) }/>
                </td>
                <td>{ packageManifest.Reference_Number_1 }</td>
            </tr>
        );
    });

    const listClientTable = listClient.map( (client, i) => {

        return (

            <tr key={i}>
                <td>{ client.name }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Edit" onClick={ () => getClient(client.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;
                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteClient(client.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const handlerOpenModalClient = (id) => {

        clearValidationClient();

        if(id)
        {
            setTitleModalClient('Update Client')
            setTextButtonSaveClient('Update');
        }
        else
        {
            clearFormClient();
            setTitleModalClient('Add Client')
            setTextButtonSaveClient('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalClientInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveStateClient = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('name', nameClient);

        clearValidationClient();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'client/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Client was registered!", {

                            icon: "success",
                        });

                        listAllClient(1);
                        clearFormClient();
                    }
                    else(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById('nameClient').style.display = 'block';
                            document.getElementById('nameClient').innerHTML     = response.errors[index][0];
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

            fetch(url_general +'client/update/'+ id, {
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
                    listAllClient(pageClient);

                    swal("Status updated!", {

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

                LoadingHide();
            });
        }
    }

    const getClient = (id) => {

        fetch(url_general +'client/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let client = response.client;

            setIdClient(client.id);
            setNameClient(client.name);

            handlerOpenModalClient(client.id);
        });
    }
    
    const deleteClient = (id) => {

        swal({
            title: "Want to delete?",
            text: "Client will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'client/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Client removed successfully!", {

                            icon: "success",
                        });

                        listAllClient(1);
                    }
                });
            } 
        });
    }

    const clearFormClient = () => {

        setIdClient(0);
        setNameClient('');
    }

    const clearValidationClient = () => {

        document.getElementById('nameClient').style.display = 'none';
        document.getElementById('nameClient').innerHTML     = '';
    }

    const modalStateInsert = <React.Fragment>
                                    <div className="modal fade" id="modalStateInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveState }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <label>State</label>
                                                                <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ name } maxLength="100" onChange={ (e) => setName(e.target.value) } required/>
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

    const modalClientInsert = <React.Fragment>
                                    <div className="modal fade" id="modalClientInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveStateClient }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title" id="exampleModalLabel">{ titleModalClient }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <label>Client</label>
                                                                <div id="nameClient" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ nameClient } maxLength="100" onChange={ (e) => setNameClient(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary">{ textButtonSaveClient }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;


    return (

        <section className="section">
            { modalStateInsert }
            { modalClientInsert }
            <div className="row">
                <div className="col-lg-6">
                    <div className="col-lg-12">
                        <div className="card">
                            <div className="card-body">
                                <h5 className="card-title">
                                    <div className="row ">
                                        <div className="col-lg-10"> 
                                            States List
                                        </div>
                                        <div className="col-lg-2">
                                            <button className="btn btn-success btn-sm pull-right" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                                <i className="bx bxs-plus-square"></i>
                                            </button>
                                        </div>
                                    </div>
                                </h5>
                                <div className="row form-group">
                                    <div className="col-lg-12"> 
                                        <input type="text" value={textSearchState} onChange={ (e) => setSearchState(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                        <br/>
                                    </div>
                                </div>
                                <div className="row form-group">
                                    <div className="col-lg-12">
                                        <button className="btn btn-primary" onClick={ () => handlerUpdateState() }>Update ANTI SCAN</button>
                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>ANTI SCAN</th>
                                                    <th>STATE</th>
                                                    <th>ACTIONS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listStateTable }
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="col-lg-12">
                        <Pagination
                            activePage={pageState}
                            totalItemsCount={totalState}
                            itemsCountPerPage={totalPageState}
                            onChange={(pageNumber) => handlerChangePageState(pageNumber)}
                            itemClass="page-item"
                            linkClass="page-link"
                            firstPageText="First"
                            lastPageText="Last"
                        />
                    </div>
                </div>
                <div className="col-lg-6">
                    <div className="col-lg-12">
                        <div className="card">
                            <div className="card-body">
                                <h5 className="card-title">
                                    <div className="row form-group">
                                        <div className="col-lg-10"> 
                                            Package List
                                        </div>
                                    </div>
                                </h5>
                                <div className="row form-group">
                                    <div className="col-lg-12"> 
                                        <input type="text" value={textSearchManifest} onChange={ (e) => setSearchManifest(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                        <br/>
                                    </div>
                                </div>
                                <div className="row form-group">
                                    <div className="col-lg-12" style={ {display: 'none'} }>
                                        <button className="btn btn-primary" onClick={ () => handlerUpdateManifest() }>Update ANTI SCAN</button>
                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>ANTI SCAN</th>
                                                    <th>PACKAGE ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listManifestTable }
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="col-lg-12">
                        <Pagination
                            activePage={pageManifest}
                            totalItemsCount={totalManifest}
                            itemsCountPerPage={totalPageManifest}
                            onChange={(pageNumber) => handlerChangePageManifest(pageNumber)}
                            itemClass="page-item"
                            linkClass="page-link"
                            firstPageText="First"
                            lastPageText="Last"
                        />
                    </div>
                </div>
                <div className="col-lg-6">
                    <div className="col-lg-12">
                        <div className="card">
                            <div className="card-body">
                                <h5 className="card-title">
                                    <div className="row form-group">
                                        <div className="col-lg-10"> 
                                            Client
                                        </div>
                                    </div>
                                </h5>
                                <div className="row form-group">
                                    <div className="col-lg-10"> 
                                        <input type="text" value={textSearchClient} onChange={ (e) => setSearchClient(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                    </div>
                                    <div className="col-lg-2">
                                        <button className="btn btn-success btn-sm pull-right" title="Agregar" onClick={ () => handlerOpenModalClient(0) }>
                                            <i className="bx bxs-plus-square"></i>
                                        </button>
                                    </div>
                                </div>
                                <div className="row form-group">
                                    
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>CLIENT</th>
                                                    <th>ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listClientTable }
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="col-lg-12">
                        <Pagination
                            activePage={pageClient}
                            totalItemsCount={totalClient}
                            itemsCountPerPage={totalPageClient}
                            onChange={(pageNumber) => handlerChangePageClient(pageNumber)}
                            itemClass="page-item"
                            linkClass="page-link"
                            firstPageText="First"
                            lastPageText="Last"
                        />
                    </div>
                </div>
            </div>
        </section>
    );
}

export default States;

// DOM element
if (document.getElementById('states')) {
    ReactDOM.render(<States />, document.getElementById('states'));
}