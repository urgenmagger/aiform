import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { ContactPayload, ContactResponse } from '../types/contact';
import { submitContact } from '../api/contactApi';
import { contactSchema } from '../schemas/contactSchema';
import AiAnalysisCard from './AiAnalysisCard';

export default function ContactForm() {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<ContactPayload>({
    resolver: zodResolver(contactSchema),
    mode: 'onBlur',
  });

  const [result, setResult] = useState<ContactResponse | null>(null);
  const [serverError, setServerError] = useState('');

  const onSubmit = async (data: ContactPayload) => {
    setServerError('');
    setResult(null);
    try {
      const response = await submitContact(data);
      setResult(response);
      reset();
    } catch (err) {
      setServerError(err instanceof Error ? err.message : 'Ошибка отправки');
    }
  };

  return (
    <section id="contact" className="contact-section">
      <div className="section-header">
        <h2 className="section-title">Форма обратной связи</h2>
        <p className="section-sub">Заполните форму и мы свяжемся с вами</p>
      </div>

      <form className="contact-form" onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="form-row">
          <div className="form-group">
            <label className="form-label" htmlFor="cf-name">Имя</label>
            <input
              id="cf-name"
              className={`form-input${errors.name ? ' form-input--error' : ''}`}
              type="text"
              placeholder="Ваше имя"
              disabled={isSubmitting}
              {...register('name')}
            />
            {errors.name?.message && <span className="form-error">{errors.name.message}</span>}
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="cf-phone">Телефон</label>
            <input
              id="cf-phone"
              className={`form-input${errors.phone ? ' form-input--error' : ''}`}
              type="text"
              placeholder="+7 (999) 123-45-67"
              disabled={isSubmitting}
              {...register('phone')}
            />
            {errors.phone?.message && <span className="form-error">{errors.phone.message}</span>}
          </div>
        </div>

        <div className="form-group">
          <label className="form-label" htmlFor="cf-email">Email</label>
          <input
            id="cf-email"
            className={`form-input${errors.email ? ' form-input--error' : ''}`}
            type="email"
            placeholder="email@example.com"
            disabled={isSubmitting}
            {...register('email')}
          />
          {errors.email?.message && <span className="form-error">{errors.email.message}</span>}
        </div>

        <div className="form-group">
          <label className="form-label" htmlFor="cf-comment">Комментарий</label>
          <textarea
            id="cf-comment"
            className={`form-textarea${errors.comment ? ' form-input--error' : ''}`}
            placeholder="Опишите ваш вопрос или проект"
            rows={5}
            disabled={isSubmitting}
            {...register('comment')}
          />
          {errors.comment?.message && <span className="form-error">{errors.comment.message}</span>}
        </div>

        {serverError && (
          <div className="analysis-error">
            <svg className="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <span>{serverError}</span>
          </div>
        )}

        <button className="btn btn--submit" type="submit" disabled={isSubmitting}>
          {isSubmitting && <span className="spinner spinner--btn" />}
          {isSubmitting ? 'Отправка...' : 'Отправить'}
        </button>
      </form>

      {result && (
        <AiAnalysisCard analysis={result.ai_analysis} onClose={() => setResult(null)} />
      )}
    </section>
  );
}
