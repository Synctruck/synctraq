import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function PackageBlocked() {

    const [listPackageBlocked, setListPackageBlocked] = useState([]);

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [comment, setComment]                  = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    useEffect(() => {

        listPackage();

    }, []);

    const listPackage = () => {

        fetch(url_general +'package-blocked/list')
        .then(res => res.json()).
        then((response) => {

                setListPackageBlocked(response.listPackageBlocked.data);
            },
        );
    }

    const handlerInsert = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('comment', comment);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-blocked/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == true)
                {
                    swal("Package saved!", {

                        icon: "success",
                    });

                    listPackage();
                    clearValidation();
                    clearForm();
                }

                if(response.status == 422)
                {
                    swal("Correct form errors!", {

                        icon: "error",
                    });

                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }
            }
        );
    }

    const clearValidation = () => {

        document.getElementById('Reference_Number_1').style.display = 'none';
        document.getElementById('Reference_Number_1').innerHTML     = '';

        document.getElementById('comment').style.display = 'none';
        document.getElementById('comment').innerHTML     = '';
    }

    const clearForm = () => {

        setReference_Number_1('');
        setComment('');
    }

    const deletePakage = (id) => {

        swal({
            title: "You want to delete?",
            text: "Package Id will be deleted!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'package-blocked/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Package Id successfully deleted!", {

                            icon: "success",
                        });

                        listPackage();
                    }
                });
            }
        });
    }

    const listPackageTable = listPackageBlocked.map( (packageBlocked, i) => {

        return (

            <tr key={i}>
                <td><b>{ packageBlocked.Reference_Number_1 }</b></td>
                <td>{ packageBlocked.comment }</td>
                <td>
                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deletePakage(packageBlocked.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12 form-group">
                                        <form onSubmit={ handlerInsert } autoComplete="off">
                                            <div className="row">
                                                <div className="col-lg-4">
                                                    <div className="form-group">
                                                        <label htmlFor="">PACKAGE ID</label>
                                                        <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                        <input type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="50" required/>
                                                    </div>
                                                </div>
                                                <div className="col-lg-6">
                                                    <div className="form-group">
                                                        <label htmlFor="">COMMENT</label>
                                                        <div id="comment" className="text-danger" style={ {display: 'none'} }></div>
                                                        <input type="text" className="form-control" value={ comment } onChange={ (e) => setComment(e.target.value) } maxLength="200" required/>
                                                    </div>
                                                </div>
                                                <div className="col-lg-2">
                                                    <div className="form-group">
                                                        <label htmlFor="" className="text-white">--</label>
                                                        <button className="btn btn-primary form-control">Save</button>
                                                    </div>
                                                </div>
                                                <div className="col-lg-12">
                                                    <div className="col-lg-2 form-group">
                                                        <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                        <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                        <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead> 
                                            <tr>
                                                <th>PACKAGE #</th>
                                                <th>COMMENT</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageTable }
                                        </tbody>
                                    </table>
                                </div>
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
        </section>
    );
}

export default PackageBlocked;

// DOM element
if (document.getElementById('packageBlocked')) {
    ReactDOM.render(<PackageBlocked />, document.getElementById('packageBlocked'));
}