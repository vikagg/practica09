class PostComments {
    constructor() {
        this.form = document.getElementById('commentForm');
        this.formContainer = document.getElementById('commentFormContainer');
        this.showFormBtn = document.getElementById('showCommentFormBtn');
        this.closeFormBtn = document.getElementById('closeCommentFormBtn');
        this.cancelBtn = document.getElementById('cancelCommentBtn');
        this.commentsList = document.getElementById('commentsList');
        this.messageEl = document.getElementById('commentMessage');
        this.textarea = document.querySelector('.comment-textarea');
        this.charCountEl = document.getElementById('charCount');
        this.submitButton = this.form?.querySelector('button[type="submit"]');
        
        this.init();
    }
    
    init() {
        this.initFormToggle();
        this.initCharCounter();
        this.initFormSubmit();
        window.replyToComment = this.replyToComment.bind(this);
        window.deleteComment = this.deleteComment.bind(this);
    }
    
    initFormToggle() {
        if (this.showFormBtn && this.formContainer) {
            this.showFormBtn.addEventListener('click', () => {
                this.showForm();
            });
        }
        
        if (this.closeFormBtn && this.formContainer) {
            this.closeFormBtn.addEventListener('click', () => {
                this.hideForm();
            });
        }
        if (this.cancelBtn && this.formContainer) {
            this.cancelBtn.addEventListener('click', () => {
                this.hideForm();
            });
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.formContainer && !this.formContainer.classList.contains('hidden')) {
                this.hideForm();
            }
        });
    }
    
    initCharCounter() {
        if (this.textarea && this.charCountEl) {
            this.textarea.addEventListener('input', () => {
                const count = this.textarea.value.length;
                this.charCountEl.textContent = count;
                
                if (count > 900) {
                    this.charCountEl.style.color = '#ff8a8a';
                } else if (count > 800) {
                    this.charCountEl.style.color = '#ffb347';
                } else {
                    this.charCountEl.style.color = '';
                }
            });
        }
    }
    
    showForm() {
        if (!this.formContainer) return;
        
        this.formContainer.classList.remove('hidden');
        if (this.showFormBtn) {
            this.showFormBtn.style.display = 'none';
        }
        
        if (this.textarea) {
            setTimeout(() => this.textarea.focus(), 100);
        }
    }
    
    hideForm() {
        if (!this.formContainer) return;
        
        this.formContainer.classList.add('hidden');
        if (this.showFormBtn) {
            this.showFormBtn.style.display = 'inline-flex';
        }
        
        if (this.form) {
            this.form.reset();
        }
        if (this.charCountEl) {
            this.charCountEl.textContent = '0';
        }
        this.clearMessage();
    }
    
    initFormSubmit() {
        if (!this.form || !this.commentsList || !this.messageEl) return;
        
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        if (this.submitButton) {
            this.submitButton.disabled = true;
            this.submitButton.textContent = 'Отправка...';
        }
        
        this.clearMessage();
        
        const formData = new FormData(this.form);
        
        try {
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error ${response.status}`);
            }
            
            if (!data.ok) {
                throw new Error(data.message || 'Ошибка при добавлении комментария');
            }
            
            this.addCommentToTop(data.comment_html);
            this.form.reset();
            if (this.charCountEl) {
                this.charCountEl.textContent = '0';
            }
            this.showMessage('Комментарий добавлен!', 'success');
            
            setTimeout(() => {
                this.hideForm();
            }, 1500);
            
        } catch (error) {
            console.error('PostComments error:', error);
            this.showMessage(error.message || 'Ошибка сети. Попробуйте снова.', 'error');
        } finally {
            if (this.submitButton) {
                this.submitButton.disabled = false;
                this.submitButton.textContent = 'Отправить комментарий';
            }
        }
    }
    
    addCommentToTop(commentHtml) {
        if (!this.commentsList) return;
        const noComments = this.commentsList.querySelector('.no-comments');
        if (noComments) {
            noComments.remove();
        }
        
        this.commentsList.insertAdjacentHTML('afterbegin', commentHtml);
        
        const newComment = this.commentsList.firstElementChild;
        if (newComment) {
            newComment.style.animation = 'none';
            void newComment.offsetWidth;
            newComment.style.animation = 'fadeIn 0.5s ease';
        }
        
        this.updateCommentsCount();
    }
    
    updateCommentsCount() {
        const commentsCountEl = document.querySelector('.comments-header-count');
        const actionBtnCount = document.querySelector('.comment-scroll-btn .count');
        const commentsCount = this.commentsList ? this.commentsList.children.length : 0;
        
        if (commentsCountEl) {
            commentsCountEl.textContent = `(${commentsCount})`;
        }
        
        if (actionBtnCount) {
            actionBtnCount.textContent = commentsCount;
        }
    }
    
    showMessage(message, type = 'info') {
        if (!this.messageEl) return;
        
        this.messageEl.textContent = message;
        this.messageEl.className = 'comment-message';
        
        if (type === 'error') {
            this.messageEl.classList.add('error-text');
        } else if (type === 'success') {
            this.messageEl.classList.add('success-text');
            setTimeout(() => {
                this.fadeOutMessage();
            }, 5000);
        }
    }
    
    clearMessage() {
        if (this.messageEl) {
            this.messageEl.textContent = '';
            this.messageEl.className = 'comment-message';
        }
    }
    
    fadeOutMessage() {
        if (!this.messageEl) return;
        
        this.messageEl.style.transition = 'opacity 0.5s ease';
        this.messageEl.style.opacity = '0';
        
        setTimeout(() => {
            if (this.messageEl) {
                this.messageEl.textContent = '';
                this.messageEl.style.opacity = '1';
                this.messageEl.style.transition = '';
                this.messageEl.className = 'comment-message';
            }
        }, 500);
    }
    
    replyToComment(username) {
        const formContainer = document.getElementById('commentFormContainer');
        const textarea = document.querySelector('.comment-textarea');
        
        if (formContainer && formContainer.classList.contains('hidden')) {
            const showBtn = document.getElementById('showCommentFormBtn');
            if (showBtn) {
                showBtn.click();
            }
        }
        
        if (textarea) {
            textarea.value = `@${username}, `;
            textarea.focus();
            const length = textarea.value.length;
            textarea.setSelectionRange(length, length);
            
            const charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = length;
            }
            
            if (length > 900) {
                charCount.style.color = '#ff8a8a';
            } else if (length > 800) {
                charCount.style.color = '#ffb347';
            }
        }
    }
    
    deleteComment(commentId) {
        if (confirm('Вы уверены, что хотите удалить этот комментарий?')) {
            console.log('Delete comment:', commentId);
            fetch('delete_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `comment_id=${commentId}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    const commentElement = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
                    if (commentElement) {
                        commentElement.remove();
                        this.updateCommentsCount();
                    }
                } else {
                    alert('Ошибка при удалении комментария: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка сети при удалении комментария');
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PostComments();
});