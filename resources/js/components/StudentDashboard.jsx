import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ChevronLeft, ChevronRight, Download, Menu, Plus, Search, Upload } from 'lucide-react';
import DashboardSidebar from './DashboardSidebar';
import StudentAnalysis from './StudentAnalysis';

function StatCard({ label, value, detail, tone }) {
    return (
        <article className={`react-stat-card ${tone}`}>
            <div>
                <p>{label}</p>
                <strong>{value}</strong>
            </div>
            <span>{detail}</span>
        </article>
    );
}

function HiddenFields({ method, csrf }) {
    return (
        <>
            <input type="hidden" name="_token" value={csrf} />
            {method ? <input type="hidden" name="_method" value={method} /> : null}
        </>
    );
}

function initialPageFromUrl() {
    const page = Number.parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
    return Number.isFinite(page) && page > 0 ? page : 1;
}

function StudentDashboard({ props }) {
    const {
        csrf,
        students,
        stats,
        analysis,
        complexities,
        filters,
        routes,
        flash,
        user,
    } = props;

    const [query, setQuery] = useState(filters.search || '');
    const [searchType, setSearchType] = useState(filters.searchType || 'sequential_name');
    const [sortType, setSortType] = useState(filters.sortType || 'student_id_bubble');
    const [currentPage, setCurrentPage] = useState(initialPageFromUrl);
    const [pageChanging, setPageChanging] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(
        () => (window.localStorage.getItem('simdex-sidebar-collapsed') || window.localStorage.getItem('studex-sidebar-collapsed') || window.localStorage.getItem('dm-sidebar-collapsed')) === 'true',
    );
    const [theme, setTheme] = useState(
        () => document.documentElement.dataset.theme || 'light',
    );
    const [pendingDeleteStudent, setPendingDeleteStudent] = useState(null);
    const pageChangeTimer = useRef(null);

    const pageSize = 10;
    const totalPages = Math.max(1, Math.ceil(students.length / pageSize));
    const visibleStudents = useMemo(() => {
        const start = (currentPage - 1) * pageSize;
        return students.slice(start, start + pageSize);
    }, [currentPage, students]);
    const appliedSearch = filters.search || '';
    const appliedSearchType = filters.searchType || 'sequential_name';
    const appliedSort = filters.sortType || 'student_id_bubble';
    const complexityRows = Object.entries(complexities);

    const tableSummary = useMemo(() => {
        if (visibleStudents.length === 0) {
            return 'No records';
        }

        if (students.length > pageSize) {
            const start = ((currentPage - 1) * pageSize) + 1;
            const end = Math.min(currentPage * pageSize, students.length);
            return `${start}-${end} of ${students.length} records`;
        }

        return `${visibleStudents.length} record${visibleStudents.length === 1 ? '' : 's'}`;
    }, [currentPage, students.length, visibleStudents.length]);

    const pageNumbers = useMemo(() => {
        if (totalPages <= 3) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        const start = Math.min(Math.max(currentPage - 1, 1), totalPages - 2);
        return [start, start + 1, start + 2];
    }, [currentPage, totalPages]);

    const alertMessage = flash.error || flash.success;
    const alertTitle = flash.error
        ? 'Action Needs Attention'
        : {
            add: 'Student Added',
            edit: 'Student Updated',
            delete: 'Student Deleted',
            login: 'Login Successful',
        }[flash.type] || 'Action Complete';
    const alertTone = flash.error ? 'error' : (flash.type || 'success');
    const [showToast, setShowToast] = useState(Boolean(alertMessage));

    useEffect(() => {
        if (!alertMessage) {
            return undefined;
        }

        setShowToast(true);
        const timer = window.setTimeout(() => setShowToast(false), 5000);
        return () => window.clearTimeout(timer);
    }, [alertMessage]);

    useEffect(() => {
        if (!pendingDeleteStudent) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setPendingDeleteStudent(null);
            }
        };

        window.addEventListener('keydown', closeOnEscape);
        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [pendingDeleteStudent]);

    useEffect(() => {
        const syncTheme = (event) => setTheme(event.detail);
        window.addEventListener('simdex-theme-change', syncTheme);
        return () => window.removeEventListener('simdex-theme-change', syncTheme);
    }, []);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(totalPages);
            const url = new URL(window.location.href);
            totalPages === 1 ? url.searchParams.delete('page') : url.searchParams.set('page', totalPages);
            window.history.replaceState({}, '', url);
        }
    }, [currentPage, totalPages]);

    useEffect(() => () => window.clearTimeout(pageChangeTimer.current), []);

    const changePage = (requestedPage) => {
        const nextPage = Math.min(Math.max(requestedPage, 1), totalPages);

        if (nextPage === currentPage || pageChanging) {
            return;
        }

        setPageChanging(true);
        setCurrentPage(nextPage);

        const url = new URL(window.location.href);
        nextPage === 1 ? url.searchParams.delete('page') : url.searchParams.set('page', nextPage);
        window.history.replaceState({}, '', url);

        window.clearTimeout(pageChangeTimer.current);
        pageChangeTimer.current = window.setTimeout(() => {
            document.getElementById('students-table')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
            setPageChanging(false);
        }, 180);
    };

    const navigateToSection = (target) => {
        document.getElementById(target)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
        setSidebarOpen(false);
    };

    const setColorTheme = (nextTheme) => {
        document.documentElement.dataset.theme = nextTheme;
        window.localStorage.setItem('simdex-theme', nextTheme);
        setTheme(nextTheme);
    };

    const toggleSidebar = () => {
        setSidebarCollapsed((current) => {
            const next = !current;
            window.localStorage.setItem('simdex-sidebar-collapsed', String(next));
            return next;
        });
    };

    return (
        <div className={`dashboard-app-layout${sidebarCollapsed ? ' sidebar-collapsed' : ''}`}>
            <DashboardSidebar
                analysis={analysis}
                collapsed={sidebarCollapsed}
                csrf={csrf}
                isOpen={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
                onNavigate={navigateToSection}
                onThemeChange={setColorTheme}
                onToggleCollapsed={toggleSidebar}
                routes={routes}
                stats={stats}
                theme={theme}
                user={user}
            />

            {sidebarOpen ? (
                <button
                    type="button"
                    className="dashboard-sidebar-overlay"
                    onClick={() => setSidebarOpen(false)}
                    aria-label="Close navigation menu"
                />
            ) : null}

            <div className="dashboard-main-area">
                <header className="dashboard-mobile-header">
                    <button type="button" onClick={() => setSidebarOpen(true)} aria-label="Open navigation menu">
                        <Menu aria-hidden="true" size={20} />
                    </button>
                    <div>
                        <strong>SIMDEX</strong>
                        <span>{user.name}</span>
                    </div>
                </header>

                <div className="react-dashboard">
                    <section className="react-hero" id="dashboard-overview">
                        <div>
                            <span className="react-kicker">Academic Data Center</span>
                            <h1>Student Index Management</h1>
                            <p>Manage student records, academic insights, and file transfers from one clear workspace.</p>
                            <div className="react-hero-highlights">
                                <span>Auto ID</span>
                                <span>Fast search</span>
                                <span>CSV / Excel</span>
                            </div>
                        </div>

                        <div className="react-hero-actions">
                            <a className="button" href={routes.create}>
                                <Plus aria-hidden="true" size={17} />
                                Add Student
                            </a>
                            <a className="button secondary" href="#students-table" onClick={(event) => {
                                event.preventDefault();
                                navigateToSection('students-table');
                            }}>
                                View Students
                            </a>
                        </div>
                    </section>

                    {showToast && alertMessage ? (
                        <div className="react-toast-stack">
                            <div className={`react-toast ${alertTone}`} role="alert">
                                <div>
                                    <strong>{alertTitle}</strong>
                                    <span>{alertMessage}</span>
                                </div>
                                <button type="button" onClick={() => setShowToast(false)} aria-label="Close alert">×</button>
                            </div>
                        </div>
                    ) : null}

                    <section className="react-stats-grid">
                        <StatCard label="Students" value={stats.total} detail="Active records" tone="violet" />
                        <StatCard label="Average GPA" value={stats.average_gpa} detail="Academic score" tone="teal" />
                        <StatCard label="Majors" value={stats.majors} detail="Study programs" tone="amber" />
                        <StatCard label="File Support" value="3" detail="CSV, XLS, XLSX" tone="rose" />
                    </section>

                    <StudentAnalysis analysis={analysis} total={stats.total} />

                    <section className="react-workspace">
                        <form className="react-panel react-filter-panel" method="GET" action={routes.index}>
                            <div className="react-panel-heading">
                                <div>
                                    <span className="react-eyebrow">Quick Search</span>
                                    <h2>Find a student</h2>
                                </div>
                            </div>

                            <div className="react-search-bar">
                                <div>
                                    <label htmlFor="search">Name or student ID</label>
                                    <input
                                        id="search"
                                        name="search"
                                        value={query}
                                        onChange={(event) => setQuery(event.target.value)}
                                        placeholder="Type a name or ID"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="search_type">Search by</label>
                                    <select
                                        id="search_type"
                                        name="search_type"
                                        value={searchType}
                                        onChange={(event) => setSearchType(event.target.value)}
                                    >
                                        <option value="sequential_name">Student name</option>
                                        <option value="linear">Exact student ID</option>
                                        <option value="binary">Fast exact ID</option>
                                    </select>
                                </div>
                                <input type="hidden" name="sort" value={sortType} />
                                <button className="button" type="submit">
                                    <Search aria-hidden="true" size={16} />
                                    Search
                                </button>
                            </div>

                            {(appliedSearch || query) ? (
                                <div className="actions" style={{ marginTop: 10 }}>
                                    <a className="button ghost compact" href={routes.index}>Clear search</a>
                                </div>
                            ) : null}
                        </form>
                    </section>

                    <section className="react-panel" id="students-table">
                        <div className="react-panel-heading">
                            <div>
                                <span className="react-eyebrow">Records</span>
                                <h2>Students</h2>
                            </div>
                            <div className="react-table-tools">
                                <span className="react-count-pill">{tableSummary}</span>
                                <form className="react-sort-form" method="GET" action={routes.index}>
                                    <input type="hidden" name="search" value={appliedSearch} />
                                    <input type="hidden" name="search_type" value={appliedSearchType} />
                                    <label className="react-table-control" htmlFor="student_sort">
                                        <span>Sort</span>
                                        <select
                                            id="student_sort"
                                            name="sort"
                                            value={sortType}
                                            onChange={(event) => {
                                                setSortType(event.target.value);
                                                event.currentTarget.form?.requestSubmit();
                                            }}
                                        >
                                            <option value="student_id_bubble">Student ID</option>
                                            <option value="name_selection">Name A–Z</option>
                                            <option value="gpa_insertion">Highest GPA</option>
                                        </select>
                                    </label>
                                </form>
                            </div>
                        </div>

                        <div className="student-file-dock" id="student-import">
                            <div className="student-file-dock-copy">
                                <span className="react-eyebrow">File Tools</span>
                                <strong>Move student records</strong>
                                <small>Import spreadsheets or export the current filtered list.</small>
                            </div>

                            <form className="student-file-action import" method="POST" action={routes.upload} encType="multipart/form-data">
                                <HiddenFields csrf={csrf} />
                                <label htmlFor="student_file">
                                    <Upload aria-hidden="true" size={16} />
                                    <span>Import file</span>
                                </label>
                                <input
                                    id="student_file"
                                    type="file"
                                    name="student_file"
                                    accept=".csv,.xls,.xlsx"
                                    aria-label="Student data file"
                                    required
                                />
                                <button className="button compact" type="submit">Import</button>
                            </form>

                            <form className="student-file-action export" method="POST" action={routes.export}>
                                <HiddenFields csrf={csrf} />
                                <input type="hidden" name="search" value={appliedSearch} />
                                <input type="hidden" name="search_type" value={appliedSearchType} />
                                <input type="hidden" name="sort" value={appliedSort} />
                                <div>
                                    <Download aria-hidden="true" size={16} />
                                    <span>Export current list</span>
                                </div>
                                <button className="button success compact" type="submit">Export CSV</button>
                            </form>
                        </div>

                        <div
                            className={`react-table-wrap${pageChanging ? ' page-changing' : ''}`}
                            aria-busy={pageChanging}
                        >
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>GPA</th>
                                        <th>Major</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visibleStudents.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="muted">No student data found.</td>
                                        </tr>
                                    ) : visibleStudents.map((student) => (
                                        <tr key={student.id}>
                                            <td data-label="ID"><span className="react-id-pill">{student.student_id}</span></td>
                                            <td data-label="Name">{student.name}</td>
                                            <td data-label="Email">{student.email}</td>
                                            <td data-label="GPA">{Number(student.gpa).toFixed(2)}</td>
                                            <td data-label="Major">{student.major || '-'}</td>
                                            <td data-label="Actions">
                                                <div className="react-row-actions">
                                                    <a className="button secondary compact" href={routes.edit.replace('__ID__', student.id)}>Edit</a>
                                                    <button className="button danger compact" type="button" onClick={() => setPendingDeleteStudent(student)}>Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {pageChanging ? (
                                <div className="student-page-loading" role="status" aria-live="polite">
                                    <span aria-hidden="true" />
                                    Loading page {currentPage}
                                </div>
                            ) : null}
                        </div>

                        <nav className="student-pagination" aria-label="Student table pagination">
                            <button
                                type="button"
                                className="student-pagination-direction"
                                disabled={currentPage === 1 || pageChanging}
                                onClick={() => changePage(currentPage - 1)}
                            >
                                <ChevronLeft aria-hidden="true" size={19} />
                                <span>Previous</span>
                            </button>

                            <div className="student-pagination-pages">
                                {pageNumbers.map((page) => (
                                    <button
                                        type="button"
                                        key={page}
                                        className={page === currentPage ? 'active' : ''}
                                        aria-current={page === currentPage ? 'page' : undefined}
                                        disabled={pageChanging}
                                        onClick={() => changePage(page)}
                                    >
                                        {page}
                                    </button>
                                ))}
                            </div>

                            <button
                                type="button"
                                className="student-pagination-direction next"
                                disabled={currentPage === totalPages || pageChanging}
                                onClick={() => changePage(currentPage + 1)}
                            >
                                <span>Next</span>
                                <ChevronRight aria-hidden="true" size={19} />
                            </button>
                        </nav>
                    </section>

                    <details className="complexity-details">
                        <summary>
                            <strong>Time Complexity</strong>
                            <span>Compact technical summary</span>
                        </summary>
                        <div className="complexity-grid">
                            {complexityRows.map(([feature, estimate]) => (
                                <article className="complexity-card" key={feature}>
                                    <strong>{feature}</strong>
                                    <span>{estimate}</span>
                                </article>
                            ))}
                        </div>
                    </details>

                    {pendingDeleteStudent ? (
                        <div className="react-modal-backdrop" role="presentation" onClick={() => setPendingDeleteStudent(null)}>
                            <div
                                className="react-modal"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="delete-student-title"
                                onClick={(event) => event.stopPropagation()}
                            >
                                <span className="react-eyebrow danger">Delete Student</span>
                                <h2 id="delete-student-title">Delete {pendingDeleteStudent.name}?</h2>
                                <p>This student record will be removed from the dashboard.</p>
                                <div className="react-modal-actions">
                                    <button className="button secondary" type="button" onClick={() => setPendingDeleteStudent(null)}>Cancel</button>
                                    <form method="POST" action={routes.destroy.replace('__ID__', pendingDeleteStudent.id)}>
                                        <HiddenFields csrf={csrf} method="DELETE" />
                                        <button className="button danger" type="submit">Delete Student</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

const mount = document.getElementById('student-dashboard');
const propsElement = document.getElementById('student-dashboard-props');

if (mount && propsElement) {
    const root = createRoot(mount);
    root.render(<StudentDashboard props={JSON.parse(propsElement.textContent)} />);
}
