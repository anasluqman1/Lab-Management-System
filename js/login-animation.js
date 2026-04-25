// Lab Login Canvas — DNA helix, blood cells, molecules & particles
// Renders behind the glassmorphism card as ambient decoration
(function () {
    const canvas = document.getElementById('labCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let W, H;
    let time = 0;

    function resize() {
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = W;
        canvas.height = H;
    }
    window.addEventListener('resize', resize);
    resize();

    const PI2 = Math.PI * 2;
    const rand = (a, b) => Math.random() * (b - a) + a;

    function proj3d(x, y, z, cx, cy) {
        const s = 400 / (400 + z);
        return { sx: cx + x * s, sy: cy + y * s, s, a: Math.max(0, Math.min(1, 1 - z / 1000)) };
    }

    // DNA helix
    const dnaStrands = [
        { cx: 0.2, cy: 0.5, pairs: 40, radius: 100, spacing: 16, depth: 350, opacity: 0.8, tilt: -0.5 },
        { cx: 0.8, cy: 0.45, pairs: 28, radius: 65, spacing: 16, depth: 650, opacity: 0.25, tilt: -0.5 },
    ];

    function drawDNA(strand) {
        const cx = W * strand.cx, cy = H * strand.cy;

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(strand.tilt);
        ctx.translate(-cx, -cy);

        const items = [];

        for (let i = 0; i < strand.pairs; i++) {
            const yOff = (i - strand.pairs / 2) * strand.spacing;
            const phase = i * 0.35 + time * 1.8;
            const drift = Math.sin(time * 0.4) * 25;

            const xA = Math.cos(phase) * strand.radius;
            const zA = Math.sin(phase) * strand.radius + strand.depth;
            const xB = Math.cos(phase + Math.PI) * strand.radius;
            const zB = Math.sin(phase + Math.PI) * strand.radius + strand.depth;
            const y = yOff + drift;

            const pA = proj3d(xA, y, zA, cx, cy);
            const pB = proj3d(xB, y, zB, cx, cy);

            items.push({ t: 0, pA, pB, z: (zA + zB) / 2 });
            items.push({ t: 1, p: pA, z: zA, c: 0 });
            items.push({ t: 1, p: pB, z: zB, c: 1 });
        }

        items.sort((a, b) => b.z - a.z);
        const op = strand.opacity;

        items.forEach(item => {
            if (item.t === 0) {
                ctx.beginPath();
                ctx.moveTo(item.pA.sx, item.pA.sy);
                ctx.lineTo(item.pB.sx, item.pB.sy);
                ctx.strokeStyle = 'rgba(6,182,212,' + (item.pA.a * 0.3 * op) + ')';
                ctx.lineWidth = 2.5 * Math.min(item.pA.s, item.pB.s);
                ctx.stroke();
            } else {
                const r = 6 * item.p.s;
                const alpha = item.p.a * op;
                ctx.beginPath();
                ctx.arc(item.p.sx, item.p.sy, r, 0, PI2);

                const g = ctx.createRadialGradient(item.p.sx, item.p.sy, 0, item.p.sx, item.p.sy, r * 3);
                const col = item.c === 0 ? '34,211,238' : '167,139,250';
                g.addColorStop(0, 'rgba(255,255,255,' + alpha + ')');
                g.addColorStop(0.3, 'rgba(' + col + ',' + (alpha * 0.6) + ')');
                g.addColorStop(1, 'rgba(' + col + ',0)');
                ctx.fillStyle = g;
                ctx.fill();
            }
        });

        ctx.restore();
    }

    // blood cells
    const bloodCells = [];
    for (let i = 0; i < 12; i++) {
        const isWhite = i < 2;
        bloodCells.push({
            x: rand(-W * 0.4, W * 0.4), y: rand(-H, H), z: rand(150, 800),
            r: isWhite ? rand(18, 25) : rand(12, 20),
            speedY: rand(-0.5, -0.1), speedX: rand(-0.2, 0.2),
            rot: rand(0, PI2), rotSpeed: rand(-0.006, 0.006),
            tilt: rand(0.6, 1), isWhite
        });
    }

    function drawBloodCells() {
        const cx = W * 0.5, cy = H * 0.5;
        bloodCells.sort((a, b) => b.z - a.z);

        bloodCells.forEach(c => {
            c.y += c.speedY;
            c.x += c.speedX + Math.sin(time * 0.5 + c.z * 0.01) * 0.1;
            c.rot += c.rotSpeed;
            if (c.y < -H * 0.7) { c.y = H * 0.7; c.x = rand(-W * 0.4, W * 0.4); }

            const p = proj3d(c.x, c.y, c.z, cx, cy);
            const r = c.r * p.s;
            if (r < 1) return;

            ctx.save();
            ctx.translate(p.sx, p.sy);
            ctx.rotate(c.rot);
            ctx.scale(1, c.tilt * (0.85 + Math.abs(Math.sin(c.rot)) * 0.15));

            if (c.isWhite) {
                const grad = ctx.createRadialGradient(0, 0, r * 0.1, 0, 0, r);
                grad.addColorStop(0, 'rgba(230,240,250,' + p.a * 0.9 + ')');
                grad.addColorStop(0.6, 'rgba(200,215,230,' + p.a * 0.7 + ')');
                grad.addColorStop(1, 'rgba(160,190,210,' + p.a * 0.3 + ')');
                ctx.beginPath();
                ctx.arc(0, 0, r, 0, PI2);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.beginPath();
                ctx.arc(r * 0.1, -r * 0.05, r * 0.35, 0, PI2);
                ctx.fillStyle = 'rgba(90,120,160,' + p.a * 0.5 + ')';
                ctx.fill();
            } else {
                const grad = ctx.createRadialGradient(0, 0, r * 0.05, 0, 0, r);
                grad.addColorStop(0, 'rgba(120,15,15,' + p.a * 0.8 + ')');
                grad.addColorStop(0.3, 'rgba(210,35,35,' + p.a * 0.95 + ')');
                grad.addColorStop(0.7, 'rgba(235,60,60,' + p.a + ')');
                grad.addColorStop(1, 'rgba(90,10,10,' + p.a * 0.6 + ')');
                ctx.beginPath();
                ctx.arc(0, 0, r, 0, PI2);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.beginPath();
                ctx.arc(-r * 0.25, -r * 0.25, r * 0.22, 0, PI2);
                ctx.fillStyle = 'rgba(255,200,200,' + p.a * 0.15 + ')';
                ctx.fill();
            }
            ctx.restore();
        });
    }

    // hexagonal molecules
    const molecules = [];
    for (let i = 0; i < 4; i++) {
        molecules.push({
            x: rand(-W * 0.3, W * 0.3), y: rand(-H * 0.4, H * 0.4), z: rand(400, 900),
            rot: rand(0, PI2), rotSpeed: rand(-0.004, 0.004),
            size: rand(14, 25), speedY: rand(-0.2, -0.05)
        });
    }

    function drawMolecules() {
        const cx = W * 0.5, cy = H * 0.5;
        molecules.forEach(m => {
            m.y += m.speedY;
            m.rot += m.rotSpeed;
            if (m.y < -H * 0.5) { m.y = H * 0.5; m.x = rand(-W * 0.3, W * 0.3); }

            const p = proj3d(m.x, m.y, m.z, cx, cy);
            const r = m.size * p.s;
            if (r < 2) return;

            ctx.save();
            ctx.translate(p.sx, p.sy);
            ctx.rotate(m.rot);
            ctx.strokeStyle = 'rgba(34,211,238,' + p.a * 0.2 + ')';
            ctx.lineWidth = 1.2 * p.s;

            ctx.beginPath();
            for (let v = 0; v < 6; v++) {
                const angle = (PI2 / 6) * v - Math.PI / 6;
                const hx = Math.cos(angle) * r, hy = Math.sin(angle) * r;
                v === 0 ? ctx.moveTo(hx, hy) : ctx.lineTo(hx, hy);
            }
            ctx.closePath();
            ctx.stroke();
            ctx.restore();
        });
    }

    // floating particles
    const particles = [];
    for (let i = 0; i < 40; i++) {
        particles.push({
            x: rand(-W * 0.5, W * 0.5), y: rand(-H, H), z: rand(100, 900),
            r: rand(0.5, 1.5), speedY: rand(-0.15, -0.03), flicker: rand(0, PI2)
        });
    }

    function drawParticles() {
        const cx = W * 0.5, cy = H * 0.5;
        particles.forEach(p => {
            p.y += p.speedY;
            p.flicker += 0.02;
            if (p.y < -H) { p.y = H; p.x = rand(-W * 0.5, W * 0.5); }

            const pr = proj3d(p.x, p.y, p.z, cx, cy);
            ctx.beginPath();
            ctx.arc(pr.sx, pr.sy, p.r * pr.s, 0, PI2);
            ctx.fillStyle = 'rgba(180,240,255,' + pr.a * (0.25 + Math.sin(p.flicker) * 0.1) + ')';
            ctx.fill();
        });
    }

    // bubbles
    const bubbles = [];
    for (let i = 0; i < 8; i++) {
        bubbles.push({
            x: rand(-80, 80), y: rand(0, H * 0.4), z: rand(250, 600),
            r: rand(4, 10), speedY: rand(-0.6, -0.15),
            wobble: rand(0, PI2), wobbleAmp: rand(0.3, 0.8)
        });
    }

    function drawBubbles() {
        const cx = W * 0.15, cy = H * 0.8;
        bubbles.forEach(b => {
            b.y += b.speedY;
            b.wobble += 0.015;
            if (b.y < -H * 0.4) { b.y = H * 0.4; b.x = rand(-80, 80); }

            const wx = b.x + Math.sin(b.wobble) * 15 * b.wobbleAmp;
            const p = proj3d(wx, b.y, b.z, cx, cy);
            const r = b.r * p.s;
            if (r < 1) return;

            ctx.beginPath();
            ctx.arc(p.sx, p.sy, r, 0, PI2);
            ctx.strokeStyle = 'rgba(200,255,255,' + p.a * 0.35 + ')';
            ctx.lineWidth = 0.8;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(p.sx - r * 0.3, p.sy - r * 0.3, r * 0.2, 0, PI2);
            ctx.fillStyle = 'rgba(255,255,255,' + p.a * 0.4 + ')';
            ctx.fill();
        });
    }

    // render loop
    function frame() {
        ctx.clearRect(0, 0, W, H);
        time += 0.008;

        drawParticles();
        drawMolecules();
        drawBubbles();
        drawDNA(dnaStrands[1]);
        drawBloodCells();
        drawDNA(dnaStrands[0]);

        requestAnimationFrame(frame);
    }

    requestAnimationFrame(frame);
})();
