import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Routes() {

    const [id, setId]            = useState(0);
    const [name, setName]        = useState('');
    const [zipCode, setZipCode]  = useState('');

    const [listRoute, setListRoute] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalRoute, setTotalRoute] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const [file, setFile] = useState('');

    useEffect(() => {

        listAllRoute(page); 

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

        listAllRoute(pageNumber);
    }

    const listAllRoute = (pageNumber) => {

        fetch(url_general +'routes/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList.data);
            setPage(response.routeList.current_page);
            setTotalPage(response.routeList.per_page);
            setTotalRoute(response.routeList.total);

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

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveRoute = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('zipCode', zipCode);
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

                        listAllRoute(1);
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
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction)
                {
                    listAllRoute(1);

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

                LoadingHide();
            });
        }
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

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

                    listAllRoute(1);

                    setViewButtonSave('none');
                }

                LoadingHide();
            },
        );
    }

    const getRoute = (id) => {

        fetch(url_general +'routes/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let route = response.route;

            setId(route.id);
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

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const listRouteTable = listRoute.map( (route, i) => {

        return (

            <tr key={i}>
                <td>{ route.zipCode }</td>
                <td>{ route.name }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getRoute(route.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteRoute(route.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveRoute }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <label>Zip Code</label>
                                                                <div id="zipCode" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ zipCode } maxLength="100" onChange={ (e) => setZipCode(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-12 form-group">
                                                                <label>Route name</label>
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

    return (

        <section className="section">
            { modalCategoryInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-9"> 
                                        Route List
                                    </div>
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
                                    <div className="col-lg-1">
                                        <button className="btn btn-success btn-sm pull-right form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group">
                                <div className="col-lg-12"> 
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>ZIP CODE</th>
                                                <th>ROUTE</th>
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