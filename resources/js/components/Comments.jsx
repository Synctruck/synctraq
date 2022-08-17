import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Comments() {

    const [id, setId]      = useState(0);
    const [description, setDescription]  = useState('');

    const [listComment, setListComment] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalComment, setTotalComment] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect(() => {

        listAllRoute(page);

    }, [textSearch])

    const handlerChangePage = (pageNumber) => {

        listAllRoute(pageNumber);
    }

    const listAllRoute = (pageNumber) => {

        fetch(url_general +'comments/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListComment(response.commentList.data);
            setPage(response.commentList.current_page);
            setTotalPage(response.commentList.per_page);
            setTotalComment(response.commentList.total);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Comment')
            setTextButtonSave('Update');
        }
        else
        {
            clearForm();
            setTitleModal('Add Comment')
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

        formData.append('description', description);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'comments/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Comment was save!", {

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

            fetch(url_general +'comments/update/'+ id, {
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

                    swal("Comment updated!", {

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

    const getComment = (id) => {

        fetch(url_general +'comments/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let comment = response.comment;

            setId(comment.id);
            setDescription(comment.description);

            handlerOpenModal(comment.id);
        });
    }

    const deleteComment = (id) => {

        swal({
            title: "You want to delete?",
            text: "Comment will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'comments/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Comment deleted successfully!", {

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
        setDescription('');
    }

    const clearValidation = () => {

        document.getElementById('description').style.display = 'none';
        document.getElementById('description').innerHTML     = '';
    }

    const listCommentTable = listComment.map( (comment, i) => {

        return (

            <tr key={i}>
                <td>{ comment.description }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getComment(comment.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteComment(comment.id) }>
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
                                                        <h5 className="modal-title" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <label>Name</label>
                                                                <div id="description" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ description } maxLength="100" onChange={ (e) => setDescription(e.target.value) } required/>
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
                                    <div className="col-lg-10"> 
                                        Comments List
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
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>DESCRIPTION</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listCommentTable }
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
                        totalItemsCount={totalComment}
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

export default Comments;

// DOM element
if (document.getElementById('comments')) {
    ReactDOM.render(<Comments />, document.getElementById('comments'));
}